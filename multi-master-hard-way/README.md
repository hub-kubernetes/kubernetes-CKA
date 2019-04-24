# Multi Master Demo Using Kubernetes the Hard Way 

##  Pre-requisite 

*     Master Node 1 - 2 cpu x 4 GB 
*     Master Node 2 - 2 cpu x 4 GB 
*     Worker Node 1 - 2 cpu x 4 GB 
*     Worker Node 2 - 2 cpu x 4 GB 
*     Control Node  - 1 cpu x 2 GB 
*     LoadBalancer  - 1 cpu x 2 GB


# Overview of SSL/TLS certificates

##  What are SSL certificates ?

> SSL certificate enables encrypted transfer of sensitive information between a client and a server. The purpose of encryption is to make sure that only the intended recipient will be able to view the information. SSL certificates are used to enable https connection between browser and websites.

##  How to generate SSL certificates ?

> There are multiple toolkits available in the market to create self signed SSL certificates. Most notable of them are - 

*  openssl
*  cfssl
*  easyrsa 

> **Self signed certificates** are useful when you want to enable SSL/TLS envryption for applications that run within your organization. These certificates are not recognized by browsers as the certificate is internal to your organization itself. In order to enable communication with any system outside your organization, you will have to set up MSSL/2 way SSL. 

> There are multiple **third party SSL certificate providers** like Verisign, Symantec, Intouch, Comodo etc. Their Certificate public key is embedded with all major browsers like chrome, IE, safari, mozilla. This enables any external user to connect to your server using a secure HTTPS connection that is recognized by the browser.  

#  Components of SSL certificate 

##  Certificate Authority (CA)

> **CA** are third party trusted entities that issues a **trusted SSL certificate**. Trusted certificate are used to create a secure connection (https) from browser/client to a server that accepts the incoming request. When you create a self-signed certificate for your organization, __**YOU**__ become the CA. 

##  Private/Public key(CSR) & Certificate

> SSL uses the concept of **private/public key pair** to authenticate, secure and manage connection between client and server. They work together to ensure TLS handshake takes place, creating a secure connection (https)

> **Private key** creates your digital signature which will eventually be trusted by any client that tries to connect to your server. With help of private key, you generate a **CSR (certificate signing request)**. Private key is kept on the server and the security of the private key is the sole responsibility of your organization. The private key should never leave your organization. 

> In contrast to private key, a **Public Key** can be distributed to multiple clients. Public Key or CSR is usually submitted to a CA like Comodo/Verisign/Entrust etc, and the CSR (formerly created by your private key) is then signed by the CA. This process generates a SSL/TLS certificate that can now be distributed to any client application. Since this certificate is signed by a trusted CA, your end users can now connect securely to your server (which contains the private key) using their browser. 

> Some third party CA also takes care of generating the private/public key pair for you. This, sometimes, is a good option in case you lose your private key or your private key is compromised. The CA provider takes care of re-keying your certificate with a new private key, and the new private key is then handed over to you. 

> When dealing with self signed certificate, its usually the organization that generates the root CA certificate and acts as the sole CA provider. Any subsequent CSR will be then signed by the root CA. This enables organizations to ensure TLS communication for applications which runs internal to them. 

##  Steps to generate a self signed certificate 

*     Choose a toolkit of your choice (openssl / easyrsa / cfssl ) -- We will use cfssl 
*     Generate root CA private key 
*     Generate a root certificate and self-sign it using the CA private key 
*     Distribute the root CA certificate on ALL the machines who wants to trust you
*     For each application/machine create a new private key 
*     Use the private key to generate a public key (CSR)
*     Ensure the Common Name Field (CN) is set accurately as per your IP address / service name or DNS
*     Sign the CSR with root CA private key and root CA certificate to generate the client certificate
*     Distribute the Client certificate to the corresponding application 

##  What certificates do we need to generate for Kubernetes ?

*   Client certificates for the **kubelet** to authenticate to the **API server**
*   Server certificate for the **apiServer endpoint**
*   Client certificates for **administrators** of the cluster to authenticate to the API server
*   Client certificates for the **apiServer** to talk to the **kubelets (nodes)**
*   Client certificate for the **apiServer** to talk to **etcd**
*   Client certificate/kubeconfig for the **controller manager** to talk to the **apiServer**
*   Client certificate/kubeconfig for the **scheduler** to talk to the **apiServer**
*   **ETCD** client/server certificates for authentication between **each other** and **apiServer**
*   Client certificate for **kube-proxy** to talk to **apiServer**


# Installing necessary software on Control node

##  Installing cfssl

` curl -s -L -o /bin/cfssl https://pkg.cfssl.org/R1.2/cfssl_linux-amd64`

` curl -s -L -o /bin/cfssljson https://pkg.cfssl.org/R1.2/cfssljson_linux-amd64`

` curl -s -L -o /bin/cfssl-certinfo https://pkg.cfssl.org/R1.2/cfssl-certinfo_linux-amd64`

` chmod +x /bin/cfssl*`

##  Install kubectl 

` sudo apt-get update && sudo apt-get install -y apt-transport-https`

` curl -s https://packages.cloud.google.com/apt/doc/apt-key.gpg | sudo apt-key add -`

` echo "deb https://apt.kubernetes.io/ kubernetes-xenial main" | sudo tee -a /etc/apt/sources.list.d/kubernetes.list`

` sudo apt-get update`

` sudo apt-get install -y kubectl`


# Generate Kubernetes Certificates using cfssl 

> As discussed above - we will be creating a **chain of trust** for kubernetes master and worker components by generating a series of **self signed certificates**. As a pre-requisute, we need a CA in order to generate certificates. 

> Since kubernetes is internal to our infrastructure, we can act as a CA by issuing a root CA certificate using cfssl and use the CA certificate to sign any subsequent CSR for various components. 

> All the required configuration files to generate the certificate are provided to you in this repository. 

##  Generate root CA certificate

> We will use ca-config.json and ca-csr.json as configuration file to generate the root CA private key and the corresponding public CSR. The public CSR will then be self signed bt the root CA private key to generate the root CA certificate. 

` cd certs`

` cfssl gencert -initca ca-csr.json | cfssljson -bare ca `

> The output will be as below

*   ca.pem  - root CA certificate
*   ca-key.pem  - root CA private key
*   ca.csr  - root CA public key which was used to create the certificate 

##  Generate client certificate for kubelet 

> In order to understand Kubelet certificate, its important to understand the concept of **node authorization** in kubernetes. Node authorization in kubernetes enables kubelet (installed on nodes) to perform READ / WRITE and AUTH API operations. Since kubelet works on PodSpec, kubelet performs API operations to API server in order to maintain the state. 

> Node Authorizer maintains a special group called as **system:nodes** and each kubelet must identify themselves as a part of this group. The identification takes place by generating a credential for kubelet within the system:nodes group. Each kubelet will have a username as **system:nodes:\<NodeName\>**. This group and user name format match the identity created for each kubelet as part of kubelet TLS bootstrapping

> Each node will have kubelet installed. Each kubelet will have a separate client CSR which will be signed by the CA created in above step. The CN for each kubelet certificate will contain the UserName of each kubelet, i.e. **system:nodes:\<NodeName\>**

> __**Installation Procedure for kubelet client certificates**__

` cd certs` 

> There are 3 files 

*   node.cfg - This file contains worker node name and IP address in the format nodeName:IP. Edit this file accordingly.
*   node-csr.json - The CSR configuration for node. 
*   createnodecert.sh - Script which will be run to generate kubelet certificate. 

` ./createnodecert.sh`

> As we have 2 worker nodes - below is the output

*   node1.pem - Public client certificate for node1 
*   node1-key.pem - Private key for node1
*   node1.csr - CSR for node1
*   node2.pem - Public client certificate for node2
*   node2-key.pem - Private key for node2
*   node2.csr - CSR for node2

> The createnodecert.sh script issues the below command for each kubelet. As you can see, the CSR is being signed using ca.pem and ca-key.pem. It takes ca-config.json to match the profile=kubernetes. 

*cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -hostname=${WORKER_HOST},${WORKER_IP} -profile=kubernetes ${WORKER_HOST}-csr.json | cfssljson -bare ${WORKER_HOST}* 


# Generate client certificate for Kubernetes core components

##  Understanding RBAC - Role Based Access Control

> **RBAC** is the implementation of Identity and Access Management (Authorization) in Kubernetes. RBAC uses rbac.authorization.k8s.io API to allow admins to dynamically configure policies through API server. Administrator can use RBAC api to grant granular roles to different users or resources. A **Role** represents a set of permissions that are applied to different resources. RBAC defines 4 top-level types - 

*   **Role**

      A **Role** can be used to grant access to a resource within a single namespace
      
*   **ClusterRole**

      A **ClusterRole** is similar to a **Role**, however, a ClusterRole extends across the cluster
      
*   **RoleBinding**

      A **RoleBinding** grants permission defined in a **Role** to a **User** or a **Set of Users**
          
*   **ClusterRoleBinding**

      A **ClusterRoleBinding** grants permission defined in a **ClusterRole** at cluster level across namespaces
      
##    Understanding Subjects

> A **RoleBinding** or **ClusterRoleBinding** will bind the permissions defined in a Role to ***Subjects***. A **Subject** is either a single user or a group of users or ServiceAccounts.  Usernames can be any custom string like "alice", "bob", "alice@example.com". 

> Kubernetes clusters have two kinds of Users. 

*     Normal Users
*     Kubernetes Managed Service Accounts 

> A kubernetes managed subject has a special prefix - **system:**. Any username with the prefix **system:** is a kubernetes managed user and is maintained & created by api server or manually through api calls. It is your administrators responsibility to ensure that no external user should be prefixed with **system:**. This may lead to system instability or crashes. The **system:** prefix can be added to either a user , group, serviceaccount, Role, ClusterRole. Few examples of kubernetes managed roles are - 

*   system:kube-scheduler - Allows access to resources required by Scheduler 
*   system:kube-controller-manager - Allows access to resources required by controller manager 
*   system:kube-proxy - Allows access to the resources required by the kube-proxy 

> More information about RBAC is provided at - https://kubernetes.io/docs/reference/access-authn-authz/rbac/

> While creating client certificates for kubernetes core componenets or admin user, its important to note that that internal user for different components are created by Kubernetes itself. Its the certificate issuers responsibility to ensure that the **Common Name (CN)** field is set correctly as **system:kube-\<COMPONENT_NAME\>**. 

##    Creating client certificate for kube-controller-manager

> The file **kube-controller-manager-csr.json** is provided that contains config for controller-manager CSR. Note the **CN** field which is kept as **system:kube-controller-manager**. 

` cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -profile=kubernetes kube-controller-manager-csr.json | cfssljson -bare kube-controller-manager`

> The output will be as below - 

*  kube-controller-manager.pem - controller-manager public certificate 
*  kube-controller-manager-key.pem - controller-manager private key
*  kube-controller-manager.csr - CSR for controller-manager

##    Creating client certificate for kube-proxy

> The file **kube-proxy-csr.json** is provided that contains config for controller-manager CSR. Note the **CN** field which is kept as **system:kube-proxy**.

` cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -profile=kubernetes kube-proxy-csr.json | cfssljson -bare kube-proxy`

> The output will be as below - 

*  kube-proxy.pem - kube-proxy public certificate 
*  kube-proxy-key.pem - kube-proxy private key
*  kube-proxy.csr - CSR for kube-proxy

##    Creating client certificate for kube-scheduler

> The file **kube-scheduler-csr.json** is provided that contains config for controller-manager CSR. Note the **CN** field which is kept as **system:kube-scheduler**.

` cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -profile=kubernetes kube-scheduler-csr.json | cfssljson -bare kube-scheduler`

> The output will be as below - 

*  kube-proxy.pem - kube-scheduler public certificate 
*  kube-proxy-key.pem - kube-scheduler private key
*  kube-proxy.csr - CSR for kube-scheduler

##    Create Admin Client certificate 

> There are a few exceptions to the kubernetes managed roles (default roles). Some default roles dont have **system:** prefix. These roles are user-facing roles, intended for admins, superusers, normal users, etc. Few examples of such roles are - 

*  cluster-admin 
*  admin

> While creating the Admin client certificate, its important to note that the **CN** field must be kept as **admin**. 

> The file **admin-csr.json** is provided which contains CSR config for Admin user. 

` cfssl gencert  -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -profile=kubernetes admin-csr.json | cfssljson -bare admin  `

> The output is as below - 

*  admin.pem - Admin client certificate 
*  admin-key.pem - Admin private key 
*  admin.csr - Admin CSR 

##    Generate server certificate for kube-apiserver 

> The file **kubernetes-csr.json** is provided that contains the default configuration of the kube-apiserver Server certificate. Since multiple applications from multiple nodes will interact with the server certificate, its important to restrict access to the server certificate so that kube-apiserver will respond to the requests coming from authorized set of IP addresses. 

` export CERT_HOSTNAME=10.32.0.1,IP_ADDRESSES_OF_MASTER,HOSTNAMES_OF_MASTERS,IP_ADDRESS_OF_LB,HOSTNAME_OF_LB,127.0.0.1,localhost,kubernetes.default`

> Details of the certified Hostnames - 

*  10.32.0.1 - Network IP address of Kubernetes Services (can be set as any range) 
*  IP address & Hostname of master - Run `ip addr` to get ip addresses. Run `hostname` to get hostname
*  IP address & address of LB - kubelet interacts with master via LB 
*  127.0.0.1 & localhost - Loopback address & localhost required by kubernetes components 
*  kubernetes.default - Used by kubernetes services for inter-namespace communication

```
echo $CERT_HOSTNAME
10.32.0.1,10.128.15.221,10.128.15.222,master1,master2,10.128.15.226,lb,127.0.0.1,localhost,kubernetes.default
```

` cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -hostname=${CERT_HOSTNAME} -profile=kubernetes kubernetes-csr.json | cfssljson -bare kubernetes`

> The output is as below - 

*  kubernetes.pem - Api-Server Server certificate 
*  kubernetes-key.pem - ApiServer private key 
*  kubernetes.csr - CSR for ApiServer Server certificate 

##    Generate Certificates for Service Account 

> Kubernetes uses the Service Account certificates to sign tokens created for each new service account. This enables service accounts to interact with multiple resources. 

> The file **service-account-csr.json** is provided. Please note that the **CN** is kept as **service-account** which matches yet another default kubernetes role. 

` cfssl gencert -ca=ca.pem -ca-key=ca-key.pem -config=ca-config.json -profile=kubernetes service-account-csr.json | cfssljson -bare service-account`

#     Distribute Certificates to corresponding nodes 

*   Worker Nodes - ca.pem , node*.pem
*   Master Nodes - ca.pem ca-key.pem kubernetes-key.pem kubernetes.pem

#     Organizing Cluster Access Using kubeconfig Files

> **kubeconfig** file helps in organizing users, clusters and namespaces. Each component that connects to kube-apiserver uses its own kubeconfig file that helps in authenticating with apiserver. The kubernetes components that requires kubeconfig are - 

* kube-controller-manager
* kube-proxy
* kube-scheduler
* Admin user (not a core component)
* kubelet

> kubeconfig file can be created using the kubectl tool. `kubectl config` command can be used to generate the initial kubeconfig file for all components. Each component should contain all the below details - 

* cluster details
  * cluster context (name) - we will use mycluster
  * Certificate Authority public certificate 
  * API server address - https://LOADBALANCER_SERVER_IP:6443
* Credentials
  * username - **system:\<group\>\<username\>** or **admin**
  * client certificate
  * client private key 
* Default context details 
  * cluster name
  * component username 

# Generate kubeconfig file 

##  Generating kubeconfig for each kubelet 

` cd certs`

` export KUBERNETES_ADDRESS=IP_ADDRESS_OF_LOADBALANCER`

> Use kubectl command to generate kubeconfig for each kubelet. 

```
for instance in node1 node2; do
  kubectl config set-cluster mycluster \
    --certificate-authority=ca.pem \
    --embed-certs=true \
    --server=https://${KUBERNETES_ADDRESS}:6443 \
    --kubeconfig=${instance}.kubeconfig

  kubectl config set-credentials system:node:${instance} \
    --client-certificate=${instance}.pem \
    --client-key=${instance}-key.pem \
    --embed-certs=true \
    --kubeconfig=${instance}.kubeconfig

  kubectl config set-context default \
    --cluster=kubernetes-the-hard-way \
    --user=system:node:${instance} \
    --kubeconfig=${instance}.kubeconfig

  kubectl config use-context default --kubeconfig=${instance}.kubeconfig
done

```

##  Generating kubeconfig for kube-proxy

` cd certs`

```
kubectl config set-cluster mycluster \
            --certificate-authority=ca.pem \
            --embed-certs=true \
            --server=https://${KUBERNETES_ADDRESS}:6443 \
            --kubeconfig=kube-proxy.kubeconfig

kubectl config set-credentials system:kube-proxy \
            --client-certificate=kube-proxy.pem \
            --client-key=kube-proxy-key.pem \
            --embed-certs=true \
            --kubeconfig=kube-proxy.kubeconfig

kubectl config set-context default \
            --cluster=mycluster \
            --user=system:kube-proxy \
            --kubeconfig=kube-proxy.kubeconfig

 kubectl config use-context default --kubeconfig=kube-proxy.kubeconfig
```

##  Generating kubeconfig for kube-controller-manager

` cd certs`

```
kubectl config set-cluster mycluster \
            --certificate-authority=ca.pem \
            --embed-certs=true \
            --server=https://127.0.0.1:6443 \
            --kubeconfig=kube-controller-manager.kubeconfig

kubectl config set-credentials system:kube-controller-manager \
            --client-certificate=kube-controller-manager.pem \
            --client-key=kube-controller-manager-key.pem \
            --embed-certs=true \
            --kubeconfig=kube-controller-manager.kubeconfig

kubectl config set-context default \
            --cluster=mycluster \
            --user=system:kube-controller-manager \
            --kubeconfig=kube-controller-manager.kubeconfig

kubectl config use-context default --kubeconfig=kube-controller-manager.kubeconfig

```

##  Generating kubeconfig for kube-scheduler 

` cd certs`

```
kubectl config set-cluster mycluster \
            --certificate-authority=ca.pem \
            --embed-certs=true \
            --server=https://127.0.0.1:6443 \
            --kubeconfig=kube-scheduler.kubeconfig

kubectl config set-credentials system:kube-scheduler \
            --client-certificate=kube-scheduler.pem \
            --client-key=kube-scheduler-key.pem \
            --embed-certs=true \
            --kubeconfig=kube-scheduler.kubeconfig

kubectl config set-context default \
            --cluster=mycluster \
            --user=system:kube-scheduler \
            --kubeconfig=kube-scheduler.kubeconfig

kubectl config use-context default --kubeconfig=kube-scheduler.kubeconfig

```

##  Generating kubeconfig for Admin user 

` cd certs` 

```
kubectl config set-cluster mycluster \
            --certificate-authority=ca.pem \
            --embed-certs=true \
            --server=https://127.0.0.1:6443 \
            --kubeconfig=admin.kubeconfig

kubectl config set-credentials admin \
            --client-certificate=admin.pem \
            --client-key=admin-key.pem \
            --embed-certs=true \
            --kubeconfig=admin.kubeconfig

kubectl config set-context default \
            --cluster=mycluster \
            --user=admin \
            --kubeconfig=admin.kubeconfig

kubectl config use-context default --kubeconfig=admin.kubeconfig

```

##  Distributing kubeconfig files to master and nodes 

* worker nodes - nodeX.kubeconfig kube-proxy.kubeconfig
* master nodes - admin.kubeconfig kube-controller-manager.kubeconfig kube-scheduler.kubeconfig



# Understanding encrypting data at rest in kubernetes

> **What are kubernetes secrets??**

  The secret object helps you store and manage sensitive information like credentials, tokens, private keys, etc. Secrets takes information as a **key-value pair**. The **key** is any user defined string and the **value** is the data that you want to encrypt. Kubernetes encodes your data to base64. 

  While creating your **Pod**, you may pass the secret either as an **environment variable** or as a **volume**. This helps prevent exposure of sensitive information. 
  
  
> **What is encryption at REST**

Its important to note that secrets are **encoded as base64** and **NOT ENCRYPTED**. All kubernetes cluster data is stored in ETCD. Just like any other data, even the secret data is stored in etcd as **plaintext**. Secrets are independent objects, that can have a lifecycle independent of the pod to which it is associated to. Apart from secrets, there might be some other resource as a part of your Custom Resource Definition that you would want to be encrypted. If anyone gets access to your **ETCD** cluster, they can retrieve your sensitive information, as it is stored in plaintext. The feature of kubernetes which allows you to encrypt your etcd data is termed as encryption at Rest. 

> **Encryptions providers supported by Kubernetes**

Name | Encryption | Considerations 
--- | --- | ---
identity | None | No encryption provided 
aescbc | AES-CBC with PKCS#7 padding | The recommended choice for encryption at rest
secretbox | XSalsa20 and Poly1305 | A newer standard of encryption. 
aesgcm | AES-GCM with random nonce | automated key rotation system is a must 
KMS | Uses envelope encryption scheme | Recomended choice while using third party tools 


> **Generating configuration for encryption at rest** 

> Generate an encryption Key 
~~~
head -c 32 /dev/urandom| base64
L0KeMGR9dmgRvfzqkELizUkcTiUpqb8lqisyzYcGtIw=
~~~

> The file encryption-config.yaml is provided that contains the first provider as **aescbc** and a secondary provider as **identity**. Kubernetes will go through the providers in order and will match aescbc as the primary encryption provider for its data at rest. 

> Copy encryption-config.yaml to all master nodes 

~~~
scp encryption-config.yaml master1:~/
scp encryption-config.yaml master2:~/

~~~


# Install and Configure ETCD 

> ETCD will be installed on both the masters. So the following installation and configuration **steps must be performed on ALL master nodes**. Both the ETCD installation will be part of a single ETCD cluster. 

* **Download ETCD binary**

` wget -q --show-progress --https-only --timestamping "https://github.com/coreos/etcd/releases/download/v3.3.5/etcd-v3.3.5-linux-amd64.tar.gz" ` 

* **Install and configure ETCD**


` tar -xvf etcd-v3.3.5-linux-amd64.tar.gz`

` sudo mv etcd-v3.3.5-linux-amd64/etcd* /usr/local/bin/`

` mkdir -p /etc/etcd /var/lib/etcd`

` cp ca.pem kubernetes-key.pem kubernetes.pem /etc/etcd/ `

~~~
ls -ltra /usr/local/bin/etcd*
-rwxr-xr-x 1 ubuntu ubuntu 16014624 May  9  2018 /usr/local/bin/etcdctl
-rwxr-xr-x 1 ubuntu ubuntu 19254528 May  9  2018 /usr/local/bin/etcd

ls -l /etc/etcd/
total 12
-rw-r--r-- 1 root root 1367 Apr 24 07:55 ca.pem
-rw------- 1 root root 1679 Apr 24 07:55 kubernetes-key.pem
-rw-r--r-- 1 root root 1558 Apr 24 07:55 kubernetes.pem

~~~























