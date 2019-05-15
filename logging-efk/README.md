# EFK (ElasticSearch - FluentD - Kibana )

##  Steps to install EFK stack on kubernetes cluster

##  Pre-requisite 

> Since EFK is a heavy application - the cluster needs to be atleast 6 cpu x 10 GB memory with 30 GB storage. EFK stack is a good example to understand the concepts of Deployment, Statefulset and DaemonSet. Lets start installing EFK stack on kubernetes - 

* Create the namespace to install the stack 

` kubectl create ns kube-logging ` 

```
kubectl get ns kube-logging
NAME           STATUS   AGE
kube-logging   Active   11s
```

* Create persistent volumes and persistent volume claims

> Elasticsearch will need a persistent volume and a corresponding claim that will be attached to the 3 replicas that we will create. The files pv.yaml and pvc.yaml contains the definition of persistent volume and persistent volume claim respectively. 
