# Part 1 - Scheduling 

### Lab Activity 1 - Label Selectors


Deploy two pods:

One pod with a label of “Tier = Web”

One pod with a label of “Tier = App”

```
apiVersion: v1 
kind: Pod 
metadata:
  name: pod1 
  labels:
    Tier: Web
spec:
  image: nginx
---
apiVersion: v1
kind: Pod
metadata:
  name: pod2
  labels:
    Tier: App
spec:
  image: nginx
```


### Lab Activity 2 - Daemonsets 

Deploy a Daemonset that leverages the nginx image

```
apiVersion: apps/v1
kind: Daemonset
metadata:
  name: nginxds
spec:
  selector:
    matchLabels:
      app: nginx
  template:
    metadata:
      labels: 
        app: nginx 
    spec:
      image: nginx
```

### Lab Activity 3 - Resource Limits

* Create a namespace

```
kubectl create namespace default-mem-example
```

* Create a LimitRange

```
vi memorylimit.yaml

apiVersion: v1
kind: LimitRange
metadata:
  name: limitrange-memory
spec:
  limits:
  - max:
      memory: 100Mi
    min:
      memory: 50Mi
    type: Container
```

* Apply this file to the namespace

```
kubectl apply -f memorylimitrange.yaml --namespace=default-mem-example
```

* Create Pod yaml with memory > 100Mi

```
apiVersion: v1
kind: Pod
metadata:
  name: nginx-memory-limit-demo
  namespace: default-mem-example
spec:
  containers:
  - name: nginx
    image: nginx
    resources:
      limits:
        memory: "800Mi"
      requests:
        memory: "600Mi"

```

* Apply the pod 

```
kubectl create -f pod.yaml 
```

**ERROR** :

Error from server (Forbidden): error when creating "pod.yaml": pods "nginx-memory-limit-demo" is forbidden: maximum memory usage per Container is 100Mi, but limit is 800Mi

### Lab Activity 4 - Multiple Schedulers

Refer to SP for multiple schedulers

### Lab Activity 5 - Schedule Pod without a scheduler

* Verify kubelet process to see if --pod-manifest-path flag exists 

```
ps -ef | grep -i kubelet | grep manifest

--pod-manifest-path=/etc/kubernetes/manifests --resolv-conf=/run/systemd/resolve/resolv.conf
```

Get the pod manifest path from the output

* Create a pod yaml 

```
vi /etc/kubernetes/manifests/nginx-pod.yaml

apiVersion: v1
kind: Pod
metadata:
  name: nginx-static-pod
spec:
  containers:
    - name: nginx
      image: nginx

```
Save the file. Pod should automatically start


### Lab Activity 6 - Display Scheduler Events

Create few pods and execute kubectl get events to list all the events. Filter out the scheduler events

### Lab Activity 7 - Know how to configure the Kubernetes Scheduler

On the master node - 

Check the pod-manifest-path flag of kubelet 

```
ps -ef | grep -i kubelet | grep manifest
```

Ideally - it should be `/etc/kubernetes/manifests`

Open the file `kube-scheduler.yaml` at `/etc/kubernetes/manifests` location. 

```
vi /etc/kubernetes/manifests/kube-scheduler.yaml
```

Add `--logtostderr=true` in the commands section - 

```
  - command:
    - kube-scheduler
    - --authentication-kubeconfig=/etc/kubernetes/scheduler.conf
    - --authorization-kubeconfig=/etc/kubernetes/scheduler.conf
    - --bind-address=127.0.0.1
    - --kubeconfig=/etc/kubernetes/scheduler.conf
    - --leader-elect=true
    - --logtostderr=true
```

Save the file and kubelet will auto-restart kube-scheduler with the new configuration. 


---

# Part 2 - Logging and Monitoring

### Lab Activity 1

```
kubectl get componentstatuses
```

**Note** - There is a bug opened in kubernetes version 1.16.x about the `kubectl get componentstatuses` not giving correct output in tabular format. Alternatively you can use the `kubectl get componentstatuses -o yaml` command to get the status if you see all status as unknown

### Lab Activity 2

Get the pod names by executing - 
```
kubectl get pods --all-namespaces
```

Get the logs by executing - 

```
kubectl logs PODNAME -n NAMESPACE_NAME
```

### Lab Activity 3

* Create nginx deployment

```
vi nginx.yaml

apiVersion: apps/v1
kind: Deployment
metadata:
  name: nginxdeploy
  labels:
    type: deployment
spec:
  replicas: 3
  selector:
    matchLabels:
      app: frontend
  template:
    metadata:
      name: nginxpod
      labels:
        app: frontend
    spec:
      containers:
        - image: nginx
          name: nginx


kubectl create -f nginx.yaml 
```

* Verify the deployment

```
kubectl get deploy
NAME          READY   UP-TO-DATE   AVAILABLE   AGE
nginxdeploy   3/3     3            3           38s
```

* Expose the deployment as ClusterIP

```
kubectl expose deploy nginxdeploy --port=80 --type=ClusterIP
```

* Verify the service 

```
kubectl get svc 
```

* Gather the events from a given pod from this deployment

```
# Get pods 
kubectl get pods 
NAME                           READY   STATUS    RESTARTS   AGE
nginx-static-pod-minikube      1/1     Running   0          29m
nginxdeploy-78b68c78c4-989zl   1/1     Running   0          2m13s
nginxdeploy-78b68c78c4-qbkzr   1/1     Running   0          2m13s
nginxdeploy-78b68c78c4-td8p5   1/1     Running   0          2m13s

* Get events from from pods 

```
kubectl get events --sort-by=.metadata.name

# Alternatively you can also execute describe command.  

kubectl describe pod nginxdeploy-78b68c78c4-td8p5
Events:
  Type    Reason     Age        From               Message
  ----    ------     ----       ----               -------
  Normal  Scheduled  <unknown>  default-scheduler  Successfully assigned default/nginxdeploy-78b68c78c4-td8p5 to minikube
  Normal  Pulling    5m36s      kubelet, minikube  Pulling image "nginx"
  Normal  Pulled     5m32s      kubelet, minikube  Successfully pulled image "nginx"
  Normal  Created    5m32s      kubelet, minikube  Created container nginx
  Normal  Started    5m32s      kubelet, minikube  Started container nginx
```

* Gather the events from the replicaset created from this deployment

```
kubectl get rs
NAME                     DESIRED   CURRENT   READY   AGE
nginxdeploy-78b68c78c4   3         3         3       6m28s

kubectl describe rs nginxdeploy-78b68c78c4
Events:
  Type    Reason            Age    From                   Message
  ----    ------            ----   ----                   -------
  Normal  SuccessfulCreate  6m51s  replicaset-controller  Created pod: nginxdeploy-78b68c78c4-qbkzr
  Normal  SuccessfulCreate  6m51s  replicaset-controller  Created pod: nginxdeploy-78b68c78c4-td8p5
  Normal  SuccessfulCreate  6m51s  replicaset-controller  Created pod: nginxdeploy-78b68c78c4-989zl

```

* Gather the events from the service from this deployment

```
kubectl get svc
NAME          TYPE        CLUSTER-IP      EXTERNAL-IP   PORT(S)   AGE
kubernetes    ClusterIP   10.96.0.1       <none>        443/TCP   69m
nginxdeploy   ClusterIP   10.103.122.83   <none>        80/TCP    7m12s

kubectl describe svc nginxdeploy

```
* Display all logs from the deployment

```
kubectl get pods --show-labels
NAME                           READY   STATUS    RESTARTS   AGE   LABELS
nginxdeploy-78b68c78c4-989zl   1/1     Running   0          10m   app=frontend,pod-template-hash=78b68c78c4
nginxdeploy-78b68c78c4-qbkzr   1/1     Running   0          10m   app=frontend,pod-template-hash=78b68c78c4
nginxdeploy-78b68c78c4-td8p5   1/1     Running   0          10m   app=frontend,pod-template-hash=78b68c78c4

kubectl logs -l app=frontend

```

















