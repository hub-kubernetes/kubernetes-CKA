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
---

## CKA Lab Part 3 - Application Lifecycle Management

### Lab 1 - Perform rolling updates on a deployment

Refer in-class demo

### Lab 2 - Change the update strategy for a deployment

Refer in-class demo

### Lab 3 - Perform a rollback on a deployment

Refer in-class demo

### Lab 4 - Scale a deployment

```
kubectl run nginx --image=nginx

kubectl scale deployment nginx --replicas=6
```

### Lab 5 - Create and run a Job

```
vi job.yaml 

apiVersion: batch/v1
kind: Job
metadata:
  name: pi
spec:
  template:
    spec:
      containers:
      - name: pi
        image: perl
        command: ["perl", "-Mbignum=bpi", "-wle", "print bpi(2000)"]
      restartPolicy: Never
  backoffLimit: 4


kubectl create -f job.yaml
```

### Lab 6 - Create and use a Config Map

```
echo "database_host" > /tmp/db_h.txt
echo "database_port" > /tmp/db_h.txt
kubectl create configmap db_config --from-file=DATABASE_HOST=/tmp/db_h.txt --from-file=DATABASE_PORT=/tmp/db_p.txt
```

### Lab 7 - Create and use Secrets

```
kubectl create secret generic db-credentials --from-literal=db-username=dbuser --from-literal=db-password=dbpassword
```

### Lab 8 - Configure a pod with specific environment variables

Refer in-class demo 

---

## CKA Lab Part 4 - Cluster

### Lab 1 - Cluster Upgrades

* Upgrade the kubeadm binary

```
apt-get upgrade kubeadm
```

* Assess the upgrade path that kubeadm has provided

```
kubeadm upgrade plan
```

* Note down the command to execute the upgrade

```
kubeadm upgrade apply v1.XX.X
```

* Upgrade Kubelet on all the nodes

```
apt-get upgrade kubelet 
```

### Cluster Upgrades - OS Upgrades

* Gracefully remove a node from active service

```
kubectl drain node worker1
```

* Gracefully return a node into active service

```
kubectl uncordon node worker1
```

### Back up etcd


```
sudo docker run --rm -v $(pwd)/backup:/backup \
    --network host \
    -v /etc/kubernetes/pki/etcd:/etc/kubernetes/pki/etcd \
    --env ETCDCTL_API=3 \
    k8s.gcr.io/etcd-amd64:3.2.18 \
    etcdctl --endpoints=https://127.0.0.1:2379 \
    --cacert=/etc/kubernetes/pki/etcd/ca.crt \
    --cert=/etc/kubernetes/pki/etcd/healthcheck-client.crt \
    --key=/etc/kubernetes/pki/etcd/healthcheck-client.key \
    snapshot save /backup/etcd-snapshot-latest.db

```

### Lab 4 - Back up Kubernetes certificates

```
sudo mkdir -p /backup

sudo cp -r /etc/kubernetes/pki backup/

```

---

## CKA Lab Part 5 - Security

### Lab 1 - RBAC within a namespace

* Create the namespace “rbac-test”

```
kubectl create ns rbac-test
```

* Create the service account “rbac-test-sa” for the “rbac-test” namespace

```
kubectl create serviceaccount rbac-test-sa -n rbac-test
```

* Create a role “rbac-test-role” that grants the following pod level resources:

```
vi role.yaml

apiVersion: rbac.authorization.k8s.io/v1
kind: Role
metadata:
  namespace: rbac-test
  name: rbac-test-role
rules:
- apiGroups: [""] 
  resources: ["pods"]
  verbs: ["get", "watch", "list"]

kubectl create -f role.yaml

kubectl get role -n rbac-test
NAME             AGE
rbac-test-role   10s
```

* Bind the “rbac-test-sa” service account to the “rbac-test-role” role

```
vi rolebinding.yaml

apiVersion: rbac.authorization.k8s.io/v1
kind: RoleBinding
metadata:
  name: rbac-test-rolebinding
  namespace: rbac-test

roleRef:
  kind: Role
  name: rbac-test-role
  apiGroup: rbac.authorization.k8s.io

subjects:
- kind: ServiceAccount
  namespace: rbac-test
  name: rbac-test-sa

kubectl create -f rolebinding.yaml
```

* Test RBAC is working by trying to do something the service account is not authorised to do

Refer in-class demo on user grants using rbac 

### Lab 2 - RBAC within a cluster

Refer in-class demo for rbac


### Lab 3 - Network security policy

* Create a nginx pod that listens on port 80, note the IP assigned to it.

```
kubectl run nginx --image=nginx 

kubectl get pods -o wide 
NAME                        READY   STATUS    RESTARTS   AGE   IP           NODE       NOMINATED NODE   READINESS GATES
nginx-6db489d4b7-l4p8c      1/1     Running   0          4s    172.17.0.5   minikube   <none>           <none>

```

* Create two pods that can use “curl” named busybox1 and busybox2

```
kubectl run busybox1 --image=radial/busyboxplus:curl -i --tty
```

This generates the below output - 
```
kubectl run --generator=deployment/apps.v1 is DEPRECATED and will be removed in a future version. Use kubectl run --generator=run-pod/v1 or kubectl create instead.

If you don't see a command prompt, try pressing enter.

```

Execute the curl command 

```

[ root@busybox1-684864579c-m78s5:/ ]$ curl 172.17.0.5   
<!DOCTYPE html>
<html>
<head>
<title>Welcome to nginx!</title>
<style>
```

Exit the pod 

```
[ root@busybox1-684864579c-m78s5:/ ]$ exit

```

Repeat the exact same steps for another container - busybox2

```
kubectl run busybox2 --image=radial/busyboxplus:curl -i --tty
```

* Label them with tier:jumppod

```
NAME                        READY   STATUS    RESTARTS   AGE     LABELS
busybox1-684864579c-m78s5   1/1     Running   1          3m59s   pod-template-hash=684864579c,run=busybox1
busybox2-6c6b47cd9c-jsz44   1/1     Running   1          52s     pod-template-hash=6c6b47cd9c,run=busybox2
nginx-6db489d4b7-l4p8c      1/1     Running   0          8m1s    pod-template-hash=6db489d4b7,run=nginx

kubectl label pod busybox1-684864579c-m78s5 tier=jumppod
kubectl label pod busybox2-6c6b47cd9c-jsz44 tier=jumppod

kubectl get pods --show-labels
NAME                        READY   STATUS    RESTARTS   AGE     LABELS
busybox1-684864579c-m78s5   1/1     Running   1          4m44s   pod-template-hash=684864579c,run=busybox1,tier=jumppod
busybox2-6c6b47cd9c-jsz44   1/1     Running   1          97s     pod-template-hash=6c6b47cd9c,run=busybox2,tier=jumppod
nginx-6db489d4b7-l4p8c      1/1     Running   0          8m46s   pod-template-hash=6db489d4b7,run=nginx

```

* Take a interactive shell to busybox1 and run curl 

```
kubectl attach pod busybox1-684864579c-m78s5 -i -t

[ root@busybox1-684864579c-m78s5:/ ]$ curl 172.17.0.5   
<!DOCTYPE html>
<html>
<head>
<title>Welcome to nginx!</title>

[ root@busybox1-684864579c-m78s5:/ ]$ exit
```

* Create a NetworkPolicy rule that blocks all ingress traffic to the nginx pod

Label the nginx pod first for selection - 

```
kubectl label pod nginx-6db489d4b7-l4p8c app=webserver
```

Create network policy to deny ingress to all pods with label app:webserver

```
vi networkpolicy.yaml

apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: nginx-deny-ingress
spec:
  podSelector:
    matchLabels:
      app: webserver
  policyTypes:
  - Ingress

kubectl create -f networkpolicy.yaml
networkpolicy.networking.k8s.io/nginx-deny-ingress created
```

Describe networkpolicy 

```
kubectl describe networkpolicy nginx-deny-ingress
Name:         nginx-deny-ingress
Namespace:    default
Created on:   2019-11-14 06:23:48 +0000 UTC
Labels:       <none>
Annotations:  <none>
Spec:
  PodSelector:     app=webserver
  Allowing ingress traffic:
    <none> (Selected pods are isolated for ingress connectivity)
  Allowing egress traffic:
    <none> (Selected pods are isolated for egress connectivity)
  Policy Types: Ingress
```

* Rerun the curl command from busybox1, it should fail.

```
kubectl attach pod busybox1-684864579c-6mcnj -i -t

[ root@busybox1-684864579c-6mcnj:/ ]$ curl 172.17.0.5
curl: (7) Failed to connect to 192.168.171.68 port 80: Connection timed out
```

* Create a NetworkPolicy that blocks all ingress traffic to the nginx pod with the exception of all pods labelled with tier:jumppod

```
vi allowingress.yaml

apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: allow-ingress-busybox
spec:
  podSelector:
    matchLabels:
      app: webserver
  ingress:
    - from:
      - podSelector:
          matchLabels:
            tier: jumppod


kubectl create -f allowingress.yaml
```

Verify connectivity from busybox

```
kubectl attach busybox1-684864579c-6mcnj -i -t 

[ root@busybox1-684864579c-6mcnj:/ ]$ curl 192.168.171.68   
<!DOCTYPE html>
<html>
<head>
<title>Welcome to nginx!</title>
<style>

```

### Lab 4 - Enable Pod Security Policy

Refer in-lab demo on PSP 

### Lab 5 - Create policies

Refer in-lab demo on PSP

### Lab 6 - Security Context

```
vi securitycontext.yaml

apiVersion: v1
kind: Pod
metadata:
  name: security-context-demo
spec:
  securityContext:
    runAsUser: 600
  containers:
  - name: sec-ctx-demo
    image: busybox
    command: [ "sh", "-c", "sleep 1h" ]

kubectl create -f securitycontext.yaml

kubectl exec -it security-context-demo sh

/ $ ps -ef
PID   USER     TIME  COMMAND
    1 600       0:00 sleep 1h

```

### Lab 7 - Secure persistent key value store

Refer SP videos 

---

## CKA Lab Part 6 - Storage

Refer to the below demo for complete example -

https://github.com/hub-kubernetes/kubernetes-CKA/tree/master/mariadb-statefulset

---

## CKA Lab Part 9 - Networking


### Lab 1 - Create a ClusterIP

* Create a deployment consisting of three nginx containers.

```
kubectl run nginx --image=nginx --replicas=3
```

* Create a service of type “Cluster IP” which is exposed on port 8080 that facilitates connections to the aforementioned deployment, which listens on port 80

```
kubectl expose deploy nginx --port=8080 --target-port=80 --type=ClusterIP
```

* Test connectivity

```
kubectl get svc
NAME         TYPE        CLUSTER-IP      EXTERNAL-IP   PORT(S)    AGE
nginx        ClusterIP   10.100.93.142   <none>        8080/TCP   4s

root@master:~# curl 10.100.93.142:8080
<!DOCTYPE html>
<html>
<head>
<title>Welcome to nginx!</title>
<style>


```

### 



















