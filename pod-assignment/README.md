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


* NodeAffinity

> NodeSelector is a simple way of assigning pod to a node. However it defines a strict regulation that the pod will always be assigned to a node that matches the selector. Now that we all have deleted the label from the node, lets run the above example once again - 

` kubectl create -f nginx-nodeselector.yaml`

Observations - 

~~~
kubectl get pods 
NAME    READY   STATUS    RESTARTS   AGE
nginx   0/1     Pending   0          7s
~~~

> The Pod is in Pending state - NodeSelector enforces that the pod should always search for the node with the label - app=frontend. This is called as a hard rule. Hard rule is basically like a binary operator, its either 0 or 1. There is no state to determine if any nodes cannot satisfy the requirement of the pod, then what should be done in this case. 

> Node affinity solves this problem for us by defining a set of **Hard Rules** and **Soft Rules**. Hard Rules determines the strict scheduling rules for a pod and Soft Rules can be used to prefer selections from the outcomes of the Hard Rules. 

>	Lets add some labels to our nodes - 

`	kubectl label node knode1 zone=us-central-1`

`	kubectl label node knode2 zone=eu-west-1`

`	kubectl label node knode2 drbackup=europe`

~~~
kubectl get nodes knode1 knode2 --show-labels
NAME     STATUS   ROLES    AGE    VERSION   LABELS
knode1   Ready    <none>   5d6h   v1.14.2   beta.kubernetes.io/arch=amd64,beta.kubernetes.io/os=linux,kubernetes.io/arch=amd64,kubernetes.io/hostname=knode1,kubernetes.io/os=linux,zone=us-central-1
NAME     STATUS   ROLES    AGE    VERSION   LABELS
knode2   Ready    <none>   5d6h   v1.14.1   beta.kubernetes.io/arch=amd64,beta.kubernetes.io/os=linux,drbackup=europe,kubernetes.io/arch=amd64,kubernetes.io/hostname=knode2,kubernetes.io/os=linux,zone=eu-west-1

~~~

>	As you can see our labels are now added. Lets now look at the hard and soft rules provided by nodeAffinity. 

1.	NodeAffinity.RequiredDuringSchedulingIgnoredDuringExecution - rule is “required during scheduling” but has no effect on an already-running Pod.

2.	NodeAffinity.PreferredDuringSchedulingIgnoredDuringExecution - rule is “preferred during scheduling” but likewise has no effect on an already-running Pod.

>	RequiredDuringSchedulingIgnoredDuringExecution and PreferredDuringSchedulingIgnoredDuringExecution together forms the basis of NodeAffinity. NodeAffinity uses matchExpressions (set based selector) to perform selection from a group of labels. Lets do a demo that uses the NodeAffinity concept to understand how it works. Below is the definition of Node Affinity in our demo - 

~~~
  affinity:
    nodeAffinity:
      requiredDuringSchedulingIgnoredDuringExecution:
        nodeSelectorTerms:
        - matchExpressions:
          - key: zone
            operator: In
            values:
            - us-central-1
            - eu-west-1
      preferredDuringSchedulingIgnoredDuringExecution:
      - weight: 1
        preference:
          matchExpressions:
          - key: drbackup
            operator: In
            values:
            - europe

~~~

>	**requiredDuringSchedulingIgnoredDuringExecution** states that the nodes that will be selected should have the zone set as either us-central-1 or us-west-1

>	**preferredDuringSchedulingIgnoredDuringExecution** states that out of the nodes that were selected, the preferred scheduling node is the node that has drbackup set as europe. 

>	The **weight** can be anu number between 1-100. As discussed above, scheduler will compute a rank (integer) for all available nodes that satisfies the affinity criteria. When nodeAffinity has multiple nodeterms, each expression is ANDed. For each preffered action the weight is added to the terms satisfied on each node. The nodes with the highest weight are preffered. 

>	The **nodeSelectorTerms** is a list of multiple matchexpressions to select nodes. 

`	kubectl create -f nginx-nodeaffinity.yaml ` 

Observations 

~~~
kubectl get pods -o wide
NAME                  READY   STATUS    RESTARTS   AGE   IP              NODE     NOMINATED NODE   READINESS GATES
nginx-affinity-node   1/1     Running   0          6s    192.168.2.204   knode2   <none>           <none>
~~~

>	The pod is now created on knode2. Knode2 satisfies the criteria of the hard rule - zone=us-west-1 and the soft rule - drbackup=europe. 

>	Lets now see what happens when we delete the label drbackup=europe from knode2 and create the same pod - 

>	Lets delete the pod first - 

` kubectl delete -f nginx-nodeaffinity.yaml `

>	We will now delete the label - drbackup=europe 

`	kubectl label node knode2 drbackup-`

>	Recreate the pod - 

`	kubectl create -f nginx-nodeaffinity.yaml`


Observations - 

>	Since the soft rule cannot be matched - multiple retries of the pod creation will create the pod on either of the node. 

```
kubectl get pods -o wide
NAME                  READY   STATUS    RESTARTS   AGE   IP             NODE     NOMINATED NODE   READINESS GATES
nginx-affinity-node   1/1     Running   0          3s    192.168.1.11   knode1   <none>           <none>


kubectl get pods -o wide
NAME                  READY   STATUS    RESTARTS   AGE   IP            NODE     NOMINATED NODE   READINESS GATES
nginx-affinity-node   1/1     Running   0          8s    192.168.2.8   knode2   <none>           <none>

```

*	PodAffinity/Anti-Affinity

>	PodAffinity is selection of a node on the basis of labels of other pods running on the node. In similar fashion, pod anti-affinity is the way to repel a pod from a node on the basis of labels of pods running on that node. Pod Affinity has multiple usecases - for ex : running an application on the same node where memcache/redis are running. Similarly, pod-antiaffinity can be used when you want to spread your pods across multiple nodes - for ex : distributing mysql pods so that they dont interfere with volumes, spreading elasticsearch pod on different nodes to create a highly available deployment. 

>	Pod affinity and antiaffinity is very similar to node affinity, the only difference is in the selection criteria. Pod affinity/antiaffinity introduces a new field called as **topology**. The topology can be any used defined key-value label. There are a few restrictions as below - 

1.	For affinity and for requiredDuringSchedulingIgnoredDuringExecution pod anti-affinity, empty topologyKey is not allowed.
2.	For requiredDuringSchedulingIgnoredDuringExecution pod anti-affinity, the admission controller LimitPodHardAntiAffinityTopology was introduced to limit topologyKey to kubernetes.io/hostname. If you want to make it available for custom topologies, you may modify the admission controller, or simply disable it.
3.	For preferredDuringSchedulingIgnoredDuringExecution pod anti-affinity, empty topologyKey is interpreted as “all topologies” (“all topologies” here is now limited to the combination of kubernetes.io/hostname, failure-domain.beta.kubernetes.io/zone and failure-domain.beta.kubernetes.io/region).


>	Lets now do a demo on pod affinity/antiaffinity. We will start by deploying a simple redis application with the label : app=cache 

`	kubectl create -f redis-cache.yaml` 

~~~
kubectl get pods --show-labels -o wide 
NAME                           READY   STATUS    RESTARTS   AGE   IP             NODE     NOMINATED NODE   READINESS GATES   LABELS
redis-cache-7d6d684f97-s7zrb   1/1     Running   0          48s   192.168.1.27   knode1   <none>           <none>            app=cache
~~~

>	We will now deploy another redis pod with the label - app=web-cache which denotes that this redis deployment will server only web traffic. The nodeselector on redis-cache-web is set as knode2, basically any node on which our previous deployment doesnt run. 

`	kubectl create -f redis-cache-web.yaml`

~~~
kubectl get pods -owide 
NAME                               READY   STATUS    RESTARTS   AGE     IP              NODE     NOMINATED NODE   READINESS GATES
redis-cache-7d6d684f97-s7zrb       1/1     Running   0          5m58s   192.168.1.27    knode1   <none>           <none>
web-redis-cache-856b7bc58b-ksf7t   1/1     Running   0          9s      192.168.2.210   knode2   <none>           <none>
~~~

>	We will now deploy a dummy application that will have both - affinity and antiaffinity as below - 

```
      affinity:
        podAntiAffinity:
          requiredDuringSchedulingIgnoredDuringExecution:
          - labelSelector:
              matchExpressions:
              - key: app
                operator: In
                values:
                - web-cache
            topologyKey: "kubernetes.io/hostname"
        podAffinity:
          requiredDuringSchedulingIgnoredDuringExecution:
          - labelSelector:
              matchExpressions:
              - key: app
                operator: In
                values:
                - cache
            topologyKey: "kubernetes.io/hostname"

```

>	The above example says that - The application will not run on any node where any pod has a label - app=web-cache. All instances of the pod will always be colocated with the redis pod which has the label - app=cache. 

`	kubectl create -f nginx.yaml` 

Observations : 

~~~
kubectl get pods -o wide 
NAME                               READY   STATUS    RESTARTS   AGE     IP              NODE     NOMINATED NODE   READINESS GATES
redis-cache-7d6d684f97-s7zrb       1/1     Running   0          11m     192.168.1.27    knode1   <none>           <none>
web-redis-cache-856b7bc58b-ksf7t   1/1     Running   0          5m13s   192.168.2.210   knode2   <none>           <none>
web-server-f98668944-grtkv         1/1     Running   0          8s      192.168.1.30    knode1   <none>           <none>
web-server-f98668944-rzmws         1/1     Running   0          8s      192.168.1.29    knode1   <none>           <none>
web-server-f98668944-wzlsr         1/1     Running   0          8s      192.168.1.28    knode1   <none>           <none>
~~~


>	As we understood - all the webserver replicas are now running on knode1 - which also serves the pod redis-cache that has the label set as - app=cache. No pods are running on the node knode2 where the redis pod is running with the label - app=web-cache 

>	Lets delete the nginx deployment - 

`	kubectl delete -f nginx.yaml`

>	We will now create another redis pod with the label - app=cache and assign it to the same node where web-cache redis pod is running (nodeselector)

`	kubectl create -f redis-cache-2.yaml`

```
kubectl get pods --show-labels -o wide 
NAME                               READY   STATUS    RESTARTS   AGE   IP              NODE     NOMINATED NODE   READINESS GATES   LABELS
redis-cache-2-7495666dfc-pd6kd     1/1     Running   0          11s   192.168.2.211   knode2   <none>           <none>            app=cache,pod-template-hash=7495666dfc
redis-cache-7d6d684f97-s7zrb       1/1     Running   0          16m   192.168.1.27    knode1   <none>           <none>            app=cache,pod-template-hash=7d6d684f97
web-redis-cache-856b7bc58b-ksf7t   1/1     Running   0          10m   192.168.2.210   knode2   <none>           <none>            app=web-cache,pod-template-hash=856b7bc58b
```

>	Create the nginx deployment once again to verify antiaffinity. Webserver pod will still repel knode2

~~~
kubectl create -f nginx.yaml 

kubectl get pods -o wide
NAME                               READY   STATUS    RESTARTS   AGE     IP              NODE     NOMINATED NODE   READINESS GATES
redis-cache-2-7495666dfc-pd6kd     1/1     Running   0          2m29s   192.168.2.211   knode2   <none>           <none>
redis-cache-7d6d684f97-s7zrb       1/1     Running   0          19m     192.168.1.27    knode1   <none>           <none>
web-redis-cache-856b7bc58b-ksf7t   1/1     Running   0          13m     192.168.2.210   knode2   <none>           <none>
web-server-f98668944-5dljp         1/1     Running   0          13s     192.168.1.32    knode1   <none>           <none>
web-server-f98668944-77q5j         1/1     Running   0          13s     192.168.1.31    knode1   <none>           <none>
web-server-f98668944-k78mb         1/1     Running   0          13s     192.168.1.33    knode1   <none>           <none>
~~~



















