# kubernetes-ingress
## Demo regarding kubernetes Ingress with Nginx ingress controller

This demo shows how to install Nginx ingress controller and managing services using ingress through external DNS 
There are multiple ways to install the controller, we will focus on deploying the controller as a Deployment with 
Nodeport Service type

## Pre-requisite -

1. Working kubernetes cluster 
2. git installed on the machine 
3. Access to atleast one DNS that can be configured (optional) 

## Steps - 

## 1. Install ingress controller on your Kubernetes cluster

    a. git clone https://github.com/nginxinc/kubernetes-ingress.git (official kubernetes nginx github repository)
    
    b. cd kubernetes-ingress/deployments
    
    c. Create the namespace and service account - kubectl apply -f common/ns-and-sa.yaml
    
    d. Create the secret for TLS certificate - kubectl apply -f common/default-server-secret.yaml
    
    e. Create the configmap for Nginx controller configuration - kubectl apply -f common/nginx-config.yaml
    
    f. Configure RBAC by creating a cluster role - kubectl apply -f rbac/rbac.yaml
    
    g. Deploy the ingress controller as a deployment - kubectl apply -f deployment/nginx-ingress.yaml
    
    h. You can also use daemonset to deploy the controller, we are using deployment as the controller to deploy ingress 
    
    i. Verify the ingress controller is running - kubectl get pods --namespace=nginx-ingress
    
    j. Expose the ingress controller by creating a service of type NodePort - kubectl create -f service/nodeport.yaml
    
    k. In case you are using managed Kubernetes Instances using GKE / AWS / AZURE you can create the service type as 
       Loadbalancer
    
    l. Verify the service - kubectl get svc -n=nginx-ingress
    
        nginx-ingress   NodePort   10.104.170.46   <none>        80:30982/TCP,443:31542/TCP   2m2s
        
    m. Note the HTTP and HTTPS port (30982 and 31542). 
    
    n. Get the internal IP address of the ingress controller - kubectl get pods --namespace=nginx-ingress -o wide
    
        nginx-ingress-755df5c4cc-2pgbv   1/1     Running   0          5m21s   192.168.1.77   knode1   <none>           <none>

    o. Note the internal IP address - 192.168.1.77
   
Now we have successfully installed NGINX Ingress controller. Lets now see the demo where we will route 3 services 
(nginx based service) using ingress. 
 
## 2.  Deploy the dummy application 

    a. Clone this repository
    
    b. You will find 3 directories - app1, app2, and app3. These 3 directories will have a Dockerfile, index.html and a 
       default.conf file. 
    
    c. The default.conf file is edited to provide the locations /app1, /app2, and /app3 respectively. Similarly index.html 
       file is modified to have different texts simulating 3 different applications deployed on nginx 
    
    d. The Dockerfile is using nginx as the base image and the files default.conf and index.html are overridden. 
    
    e. Lets build these images 
    
        1. docker login -- to login to your dockerhub repository. 
        
        2. cd app1 ; docker build . -t {YOUR_DOCKERLOGIN_ID}/nginx-app1 ; docker push {YOUR_DOCKERLOGIN_ID}/nginx-app1
        
        3. cd app2 ; docker build . -t {YOUR_DOCKERLOGIN_ID}/nginx-app2 ; docker push {YOUR_DOCKERLOGIN_ID}/nginx-app2
        
        4. cd app3 ; docker build . -t {YOUR_DOCKERLOGIN_ID}/nginx-app3 ; docker push {YOUR_DOCKERLOGIN_ID}/nginx-app3
        
        5. There are 3 files present - app1.yaml, app2.yaml and app3.yaml which contains the deployment definition of all the 
           3 images 
        
        6. kubectl create -f app1.yaml -f app2.yaml -f app3.yaml
        
        7. There is 1 file - service.yaml which contails the service definition of all the 3 deployments. 
        
        8. kubectl create -f service.yaml
        
        9. Test the deployment to see everything is working - 
        
        kubectl get svc 
        NAME         TYPE        CLUSTER-IP       EXTERNAL-IP   PORT(S)   AGE
        app1         ClusterIP   10.102.238.68    <none>        80/TCP    5s
        app2         ClusterIP   10.97.89.152     <none>        80/TCP    5s
        app3         ClusterIP   10.110.243.149   <none>        80/TCP    5s
    
        10. Curl the service IP address to get the below output 
        
            curl 10.102.238.68
            You have reached app1
            
            curl 10.97.89.152
            You have reached app2
            
            curl 10.110.243.149
            You have reached app3
            
Now that we have successfully deployed our application, its time to create an ingress resource that will route traffic to all 
the 3 services 

## 3. Deploy ingress resource

    a. There is a file ingress.yaml that contains ingress definition to route the traffic 
    
    b. The ingress file contails the host as : kubernetesfederatedcluster.com, If you have your own domain, make the relevant 
       changes here. If you dont have your own domain, you can change this to any value like abc.com or example.com. 
    
    b. kubectl create -f ingress.yaml
    
    d. Verify the ingress 
    
        kubectl get ingress
        NAME          HOSTS                            ADDRESS   PORTS   AGE
        app-ingress   kubernetesfederatedcluster.com             80      4m11s

We have now successfully deployed ingress controller. We can now test the ingress in 2 Ways 

## 4. Testing ingress controller - 

###     a. You dont have your own domain name - 
    
        1. Go up to the step 1.m and 1.o where we got the IP address of the ingress controller and the Port
        
        2. export IC_IP=192.168.1.77    (Step 1.o)
        
        3. export IC_HTTP_PORT=80       (Step 1.m - Please not not to use the NodePort as we are using the IP address of 
           the ingress controller) 
        
        4. You can specify the port as 443 if your application uses SSL. We are not using SSL at the moment 
        
        5. Run the below command that hits /app1- 
        
            curl --resolve kubernetesfederatedcluster.com:$IC_HTTP_PORT:$IC_IP http://kubernetesfederatedcluster.com:$IC_HTTP_PORT/app1 --insecure
            You have reached app1
            
        6. Similarly the command to hit app2 and app3 are as below 
        
            curl --resolve kubernetesfederatedcluster.com:$IC_HTTP_PORT:$IC_IP http://kubernetesfederatedcluster.com:$IC_HTTP_PORT/app2 --insecure
            You have reached app2
            
            curl --resolve kubernetesfederatedcluster.com:$IC_HTTP_PORT:$IC_IP http://kubernetesfederatedcluster.com:$IC_HTTP_PORT/app3 --insecure
            You have reached app3
            
        7.  Make sure you change the host kubernetesfederatedcluster.com to the appropriate host that is defined in your 
            ingress file. 
        
###    b. You own your own domain name - 
    
        1. If you own your own domain name, there are certain configuration you need to do before hand
        
        2. Reserve a static external IP address with your cloud provider 
        
        3. Assign this static external IP address to any Virtual machine that is a part of your kubernetes cluster 
        
        4. if you are working with a cloud provider like GCP/AWS/AZURE/
          
            a. Create a DNS on your cloud provider 
            
            b. Associate the DNS with your owned DNS name - in my case - kubernetesfederatedcluster.com
            
            c. Update your Domain setting from your provider to use the nameservers provided by your cloud provider. 
               Remove any custom DNS setting that you might have by your domain provider. 
            
            d. Create a new A record for kubernetesfederatedcluster.com and associate the external static Ip that you reserved 
               with the A record 
            
            e. Create a new CNAME with DNS as www.kubernetesfederatedcluster.com. and associate it with the canonical name as 
               kubernetesfederatedcluster.com
            
            f. Save these changes
            
            g. Primary goal is to make sure that your DNS resolves to the external IP that you have reserved and assigned to 
               the VM within your cluster. 
            
            h. Get the nginx controller port detail from above step 1.m - this is 30982 in our case. 
            
            i. The DNS changes might take 10-15 mins to reflect. Make sure you ping your domain using - ping 
               kubernetesfederatedcluster.com and you should get the correct IP address 
            
            j. Access your ingress as below - 
            
                www.kubernetesfederatedcluster.com:30982/app1
                
                www.kubernetesfederatedcluster.com:30982/app2
                
                www.kubernetesfederatedcluster.com:30982/app3
          

    
