# NetworkPolicies

> A pod communicates with other pods within its own cluster and outside the cluster using services and external network endpoints. This is true in case of a microservice application in which the backend microservices interact with each other and the frontend microservice publishes itself to the external DNS. The concept of namespaces defines that all pods running within a single namespace can interact with each other using the service DNS. Pods outside the namespaces can communicate using the service FQDN. 

> By default all pods are non-isolated. It means that they can accept requests from anywhere. In case they are exposed externally using a loadbalancer, a pod might accept request from any source. All organizations do need some form of network isolation when it comes to deploying an application, for example - you might have a series of subnet that should not be able to access the pods OR the database that holds consumer sensitive data should not be accessed by any other applications. These isolations are provided with help of network policies. 

> Kubernetes supports the **NetworkPolicy** resource. However, this resource is useless unless the underlying network plugin (CNI) supports the implementation. Major network plugins like Calico, Flannel, Canal etc supports NetworkPolicy implementation in their own way. As long as one of the supported CNIs are installed, you can deploy networkpolicies on your cluster to implement pod isolations. 

> As per version 1.14 of kubernetes - a complete reference of network policies are available at - https://kubernetes.io/docs/reference/generated/kubernetes-api/v1.14/#networkpolicy-v1-networking-k8s-io


# Calico for Kubernetes 

> Calico is a network plugin supported by kubernetes and it helps implement networking and networkpolicies in kubernetes cluster. Calico is supported by multiple cloud providers like AWS, GCP, Azure and comes with a pure IP networking fabric to provide high performance networking. Calico can also be used on private cloud by configuring BGP peering. More information on Calico and installation instructions are provided at calico's official website - https://docs.projectcalico.org/v2.0/getting-started/kubernetes/

# Implement a network policy using Calico 

> We will implement a network policy in a separate namespace - 

` kubectl create ns networkdemo `

> Lets create a mysql deployment and expose the mysql deployment using a service type Cluster IP 

` kubectl create -f mysqlpod.yaml ` 

```
kubectl get pods -n networkdemo
NAME                     READY   STATUS    RESTARTS   AGE
mysql-56546566c7-cpmsf   1/1     Running   0          3m27s
```

> Create a service for pod mysql - This service will be used by our webserver application 

` kubectl expose deploy mysql --port=3306 --type=ClusterIP -n networkdemo`

```
kubectl get svc -n networkdemo
NAME    TYPE        CLUSTER-IP       EXTERNAL-IP   PORT(S)    AGE
mysql   ClusterIP   10.105.107.237   <none>        3306/TCP   60s
```

` kubectl create -f app.yaml ` 

```
kubectl get pods -n networkdemo
NAME                            READY   STATUS    RESTARTS   AGE
interpoddemo-6fddddbb74-jtsds   1/1     Running   0          16s
interpoddemo-6fddddbb74-p8dk6   1/1     Running   0          16s
interpoddemo-6fddddbb74-wpznb   1/1     Running   0          16s
mysql-56546566c7-cpmsf          1/1     Running   0          7m50s
```

> Create a service for the PHP webserver POD. 

` kubectl expose deploy interpoddemo --port=80 --type=ClusterIP -n networkdemo` 

```
kubectl get svc -n networkdemo
NAME           TYPE        CLUSTER-IP       EXTERNAL-IP   PORT(S)    AGE
interpoddemo   ClusterIP   10.102.212.191   <none>        80/TCP     32s
mysql          ClusterIP   10.105.107.237   <none>        3306/TCP   5m45s
```

> Insert dummy data in the mysql DB. The file db.txt is provided with dummy data. Create a database with name **db1** and use the db.txt to create a table and insert sample data. 

> Currently all our pods have ingress and egress routes enabled. They are in non-isolated state. We can verify that by creating a simple busybox pod - 

` kubectl run -n networkdemo demopod --rm -ti --image busybox /bin/sh`

` wget -q --timeout=5 http://interpoddemo/index.php -O - ` 

> The above command should give you the php file as output











