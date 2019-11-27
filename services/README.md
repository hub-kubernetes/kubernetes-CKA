# Kubernetes Service 

A service is an abstract way to expose an application running on a set of Pods as a network service. With Kubernetes you don’t need to modify your application to use an unfamiliar service discovery mechanism. Kubernetes gives Pods their own IP addresses and a single DNS name for a set of Pods, and can load-balance across them.

Lets first create a simple deployment - 

```
kubectl run nginx --image=nginx 
```

We will now use the deployment `nginx` to create service resource on top of it 

A service can be created either using a YAML file or by using the kubectl utility 

## Cluster IP 

```
vi service.yaml 

apiVersion: v1
kind: Service
metadata:
  name: nginx-clusterip
spec:
  selector:
    run: nginx
  ports:
    - protocol: TCP
      port: 80
      targetPort: 80
  type: ClusterIP

```

OR 

```
kubectl expose deploy nginx --port=80 --type=ClusterIP --name=nginx-clusterip
```

## NodePort 

```
vi service-nodeport.yaml

apiVersion: v1
kind: Service
metadata:
  name: nginx-nodeport
spec:
  selector:
    app: MyApp
  ports:
    - protocol: TCP
      port: 80
      targetPort: 80
  type: NodePort

```

OR 

```
kubectl expose deploy nginx --port=80 --type=NodePort --name=nginx-nodeport

```

## LoadBalancer (Only on top of cloud providers)

```
vi service-lb.yaml

apiVersion: v1
kind: Service
metadata:
  name: nginx-lb
spec:
  selector:
    app: MyApp
  ports:
    - protocol: TCP
      port: 80
      targetPort: 80
  type: LoadBalancer
```

OR 

```
kubectl expose deploy nginx --port=80 --type=LoadBalancer --name=nginx-lb
```

To view the services use the `kubectl get service` command 
