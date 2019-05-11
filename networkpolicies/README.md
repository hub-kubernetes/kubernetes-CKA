# NetworkPolicies

> A pod communicates with other pods within its own cluster and outside the cluster using services and external network endpoints. This is true in case of a microservice application in which the backend microservices interact with each other and the frontend microservice publishes itself to the external DNS. The concept of namespaces defines that all pods running within a single namespace can interact with each other using the service DNS. Pods outside the namespaces can communicate using the service FQDN. 

> By default all pods are non-isolated. It means that they can accept requests from anywhere. In case they are exposed externally using a loadbalancer, a pod might accept request from any source. All organizations do need some form of network isolation when it comes to deploying an application, for example - you might have a series of subnet that should not be able to access the pods OR the database that holds consumer sensitive data should not be accessed by any other applications. These isolations are provided with help of network policies. 

> Kubernetes supports the **NetworkPolicy** resource. However, this resource is useless unless the underlying network plugin (CNI) supports the implementation. Major network plugins like Calico, Flannel, Canal etc supports NetworkPolicy implementation in their own way. As long as one of the supported CNIs are installed, you can deploy networkpolicies on your cluster to implement pod isolations. 

> As per version 1.14 of kubernetes - a complete reference of network policies are available at - https://kubernetes.io/docs/reference/generated/kubernetes-api/v1.14/#networkpolicy-v1-networking-k8s-io

