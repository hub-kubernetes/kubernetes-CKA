# Pod Assignment

> kube-scheduler is the component responsible of assigning pods to nodes which can supply enough resources for a pod to execute. In common scenarios this would work as its now the schedulers responsibility to ensure that it keeps a status of all the kubernetes nodes and only assign healthy nodes to upcoming pods. A kubernetes admin doesnt need to worry about which pod is assigned to which node in most cases. However there are multiple cases where you might want to assign pods to specific nodes. Few usecases are as below - 

* Critical database pods might need to get scheduled on nodes that have persistent disks
* Pod scheduling as per availability zones
* Service switch for the same pod 
* Co-locate pod as per affinity to another pod, ex - a backend pod might need tight interaction with a messaging Queue 

> In the above scenarios, we cannot rely on kube-scheduler directly. A quick look into kube-scheduler shows - 

~~~
func (f *ConfigFactory) getNextPod() *v1.Pod {
	for {
		pod := cache.Pop(f.podQueue).(*v1.Pod)
		if f.ResponsibleForPod(pod) {
			glog.V(4).Infof("About to try and schedule pod %v", pod.Name)
			return pod
		}
	}
}

~~~

> kube-scheduler maintains a Queue for all Pods. It means that any new pod that comes up goes directly into the podQueue datastructure. Once inside the Pod Queue, its now upto the scheduler to find the appropriate node. Each node is assigned a rank based on the below considerations 

* NoDiskConflict
* NoVolumeZoneConflict
* PodFitsHostPorts
* HostName
* MatchNodeSelector etc. 


> The above are considered as filters, and as and when scheduler drills down through all the filtering policies, it determines the best node that can accomodate the pod. If you carefully observe the above filtering criterias, there is a **HostName** and a **MatchNodeSelector** policy that provides specifications for an end user to assign nodes to a Pod directly. 


##  Pod Assignment strategies 

* nodeSelector 

> `nodeSelector` is a field of PodSpec. It specifies a map of key-value pairs. For the pod to be eligible to run on a node, the node must have each of the indicated key-value pairs as labels. This is the simplest form of selector. Lets do a quick demo to understand how node selectors work - 

> Label any one node - 

` kubectl label node knode1 app=frontend `

```
kubectl get nodes knode1 --show-labels 
NAME     STATUS   ROLES    AGE    VERSION   LABELS
knode1   Ready    <none>   5d5h   v1.14.2   **app=frontend**,beta.kubernetes.io/arch=amd64,beta.kubernetes.io/os=linux,kubernetes.io/arch=amd64,kubernetes.io/hostname=knode1,kubernetes.io/os=linux
```

> We will now deploy a nginx Pod that has the attribute - nodeSelector which matches the label **app=frontend**

` kubectl create -f nginx-nodeselector.yaml`

> Verify if node selection has worked 

```
kubectl get pods -o wide 
nginx                  1/1     Running     0          34s   192.168.1.22    knode1   <none>           <none>

```

> To remove a label from a node - 

` kubectl edit node knode1`

> Remove the entry app=frontend from .metadata.labels section and save the node config. 


* Node affinity / antiaffinity 

> NodeSelector is a simple way of assigning pod to a node. However it defines a strict regulation that the pod will always be assigned to a node that matches the selector. Now that we all have deleted the label from the node, lets run the above example once again - 

` kubectl create -f nginx-nodeselector.yaml`

Observations - 

~~~
kubectl get pods 
NAME    READY   STATUS    RESTARTS   AGE
nginx   0/1     Pending   0          7s
~~~

> The Pod is in Pending state - NodeSelector enforces that the pod should always search for the node with the label - app=frontend. This is called as a hard rule. Hard rule is basically like a binary operator, its either 0 or 1. There is no state to determine if any nodes cannot satisfy the requirement of the pod, then what should be done in this case. 

> Node affinity/antiaffinity solves this problem for us by defining a set of **Hard Rules** and **Soft Rules**. Hard Rules determines the strict scheduling rules for a pod and Soft Rules can be used to override certain limitations that a Pod cant achieve because of the Hard rules















