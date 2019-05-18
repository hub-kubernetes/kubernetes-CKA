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
  
- **RoleBinding and ClusterRoleBinding**

  - **RoleBinding** - RoleBinding binds a **subject** to a **Role**. It means, a subject is now assigned certian authorization policies. Since Roles are confined to a single namespace, the subjects have these authorization on the same namespace. Its important to understand that the serviceaccounts or users accessing the Roles must be present in the same namespace. 
  
  - **ClusterRoleBinding** - Similar to Rolebinding, ClusterRoleBinding binds a **Subject** to a **ClusterRole**. Since ClusterRole spawns across the cluster, the subject will have these policies on all namespaces across your cluster. 
  
> Now that we have undestood the primary concepts regarding RBAC, lets do a demo on creating a kubernetes user with mimimal authorization - 

> Lets start off by creating a user `developer` on our linux machine from where kubectl is run - 

` useradd -m developer` 

` sudo -iu developer`

```
pwd
/home/developer
```

> Just like we created Certificates for Admin user - we will now create a certificate for developer user. We need the CA certificate (ca.crt / ca.pem) and the CA private key (ca.key / ca-key.pem)

```
mkdir developercerts
cd developercerts
cp ~/adobe-training/multi-master-hard-way/certs/ca-key.pem .
cp ~/adobe-training/multi-master-hard-way/certs/ca.pem .
```

> We will now create a CSR for the user - developer and use the CA certificates to sign them. In order to create these certificates we need to set the CN as developer while creating the CSR. This tells kubernetes that any end user using these certificates will have the same access as the developer user. 

~~~
openssl genrsa -out user.key 2048
openssl req -new -key user.key -out user.csr -subj "/CN=user/O=developer"
openssl x509 -req -in user.csr  -CA ca.pem -CAkey ca-key.pem  -CAcreateserial -out user.crt -days 500
~~~

> Below are the files generated - 

~~~
-rw------- 1 root root 1675 May 18 11:05 user.key
-rw-r--r-- 1 root root  911 May 18 11:08 user.csr
-rw-r--r-- 1 root root 1111 May 18 11:09 user.crt
-rw-r--r-- 1 root root   17 May 18 11:09 ca.srl
~~~


> Create RBAC policies for the developer user - We will create a namespace called `development` and provide GET, LIST, WATCH, CREATE, UPDATE actions to ONLY Deployments, Replicasets and Pods on this namespace. Below is the definition of the ROLE and the corresponding ROLEBINDING 

~~~
role.yaml

kind: Role
apiVersion: rbac.authorization.k8s.io/v1beta1
metadata:
  namespace: development
  name: developer-role
rules:
- apiGroups: ["", "extensions", "apps"]
  resources: ["deployments", "replicasets", "pods"]
  verbs: ["get", "list", "watch", "create", "update"]

  
rolebinding.yaml

kind: RoleBinding
apiVersion: rbac.authorization.k8s.io/v1beta1
metadata:
  name: development-binding
  namespace: development
subjects:
- kind: User
  name: developer
  apiGroup: ""
roleRef:
  kind: Role
  name: developer-role
  apiGroup: ""

~~~

Observations - 

> In the role - the namespace is specified as development and the rules specifies the policies that will be granted by this role. 

> In the RoleBinding - the namespace is specified as development, the subject is specified as developer (same value as that from CN) and the binding is between the developer user and the Role created above - developer-role. We will now create these policies - 

` kubectl create ns development`

` kubectl create -f role.yaml -f rolebinding.yaml` 

~~~
kubectl create -f role.yaml -f rolebinding.yaml
role.rbac.authorization.k8s.io/developer-role created
rolebinding.rbac.authorization.k8s.io/development-binding created
~~~

> We will now distribute the certificates to the end user - i.e. the developer user created on our system. We **SHOULD NOT** provide the CA private key to any user. 

` cd developercerts`

` rm ca-key.pem `

` cd .. ` 

` cp -R developercerts ~developer/

` chown -R developer:developer ~developer/developercerts/ `

` sudo -iu developer`




  















