# Kubernetes Dashboard installation steps 

* Create self signed certs 
~~~
mkdir certs
cd certs
openssl genrsa -out dashboard.key 2048
openssl rsa -in dashboard.key -out dashboard.key
openssl req -sha256 -new -key dashboard.key -out dashboard.csr -subj '/CN=localhost'
openssl x509 -req -sha256 -days 365 -in dashboard.csr -signkey dashboard.key -out dashboard.crt

~~~

* Add certs as secrets to be consumed by dashboard 

` kubectl -n kube-system create secret generic kubernetes-dashboard-certs --from-file=/PATH_TO_CERTS` 

* Create the dashboard deployment 

` kubectl apply -f https://raw.githubusercontent.com/kubernetes/dashboard/v1.10.1/src/deploy/recommended/kubernetes-dashboard.yaml `

* Create a pod security policy and assign the psp to dashboard service account 

` kubectl create -f podsecuritypolicy.yaml` 

* Create a role for dashboard service account to use pod security policy

~~~
kubectl -n kube-system create role psp:dashboard --verb=use --resource=podsecuritypolicy --resource-name=dashboard
kubectl -n kube-system create rolebinding kubernetes-dashboard-policy --role=psp:dashboard --serviceaccount=kube-system:kubernetes-dashboard
kubectl --as=system:serviceaccount:kube-system:kubernetes-dashboard -n kube-system auth can-i use podsecuritypolicy/dashboard

~~~

* Create Admin user for dashboard and a corresponding clusterrolebinding

` kubectl create -f serviceaccount.yaml -f clusterrolebinding.yaml `

* Edit the dashboard service and expose it as either a loadbalancer or a nodeport. 

* Get the token for admin user from the secrets 

` kubectl -n kube-system describe secret $(kubectl -n kube-system get secret | grep admin-user | awk '{print $1}') `



  




