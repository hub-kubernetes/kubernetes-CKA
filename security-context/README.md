# Understanding Kubernetes security contexts

> kubernetes security contexts allows the kubernetes admin to add the layer of security, authentication and authorization to Kubernetes. We have already seen one way of securing kubernetes when we generated the self signed certificates for different kubernetes components to enable SSL connectivity. There are multiple other ways of securing your cluster. Lets understand some ways to secure our cluster 


# RBAC - Role Based Access Control

> RBAC essentially provides authorizations for subjects (end user, application, kubernetes components) to perform actions on kubernetes resources (pods, service, namespaces, deployments, etc). kube-apiserver exposes the `rbac.authorization.k8s.io` API object which is used by the RBAC resources to provide authorization. Lets understand the building blocks for RBAC - 

- **Subjects**
  - **Users** - This can be any end user (OS user) like kubernetes administrator, developer, OPS, SA etc. This user needs to have appropriate permission in the kubernetes cluster to access resources. For example - an admin user will need full access to the cluster whereas a developer might need access to only a single namespace, similarly a Kubernetes user might just need GET, LIST, WATCH access on kubernetes cluster and not DELETE
  
  - **ServiceAccount** - Serviceaccounts are used by applications running in kubernetes. It is a kubernetes cluster level account (so not physical user in your OS) which is assigned to pods. Service accounts are provided with authorization policies which allows pods to interact with other resources in your cluster 
  
  - **Groups** - Groups denotes a group of users which needs same level of access. 
  
- **Resources** - resources are kubernetes entities like Pods, deployments, hpa, persistent volumes etc and certain sub resources like nodes/stats, pod/logs. These are the entities to which authorization is provided by RBAC. 

- **Verbs** - verbs are actions which specifies the type of authorization provided on the Resources. Verbs are GET, LIST, WATCH, DELETE, CREATE, UPDATE, PATCH. you can fine grain the policies by using the correct verbs. for example - an admin needs all these actions on all resources, a developer might need these GET, LIST, CREATE, WATCH on a single namespace and doesnt need DELETE, UPDATE, PATCH. 

- **Roles and ClusterRoles** - 

  - **Roles** - Roles are basically your security profile which combines a set of resources with the corresponding verbs. Roles are limited to a single namespace
  
  - **ClusterRole** - Very similar to Roles, they are security profile for the entire cluster
  
- **RoleBinding and ClusterRoleBinding

  - **RoleBinding** - RoleBinding binds a **subject** to a **Role**. It means, a subject is now assigned certian authorization policies. Since Roles are confined to a single namespace, the subjects have these authorization on the same namespace. Its important to understand that the serviceaccounts or users accessing the Roles must be present in the same namespace. 
  
  - **ClusterRoleBinding** - Similar to Rolebinding, ClusterRoleBinding binds a **Subject** to a **ClusterRole**. Since ClusterRole spawns across the cluster, the subject will have these policies on all namespaces across your cluster. 
  
> Now that we have undestood the primary concepts regarding 
  















