# Horizontal Pod autoscaler

##  Install Metrics server - 

> Clone this repository and install metrics server. Please do note that this setup is good for dev/qa environment. A lot of considerations must be put while installing metrics server in production environment. The official metrics-server repository is kept at https://github.com/kubernetes-incubator/metrics-server and we are using the same repo with few changes. 

> The file `metrics-server-deployment.yaml` is edited with the below statements - 

~~~
        command:
        - /metrics-server
        - --kubelet-insecure-tls
        - --kubelet-preferred-address-types=InternalIP

~~~

> The above command has the flag `--kubelet-insecure-tls` set which ignores strict check of kubelet certificates. In production you will definitely have a single CA private key that will be used to sign all kubelets. Please check the official metrics server documentation on other flags. 

> Install the metrics server 

` cd metrics-server` 

` kubectl create -f . ` 


##  Create nginx deployment

> It is mandatory to set requests on  cpu utilization as HPA requires CPU metrics. 

` kubectl create -f nginx.yaml` 

##  Create HPA resource 

` kubectl autoscale deploy nginx --min=3 --max=5 --cpu-percent=40` 

> This might take a minute or two to show up - 

~~~
kubectl get hpa 
NAME    REFERENCE          TARGETS   MINPODS   MAXPODS   REPLICAS   AGE
nginx   Deployment/nginx   0%/40%    3         5         3          55s
~~~

##  Test the HPA using apache bench 

` apt-get install apache2-utils` 

` kubectl expose deploy nginx --port=80 --type=ClusterIP` 

> Get the service IP address using ` kubectl get svc` 

` ab -n 500000 -c 1000 http://10.97.161.152/` 
