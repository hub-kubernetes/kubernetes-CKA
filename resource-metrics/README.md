# Resource Metrics in Kubernetes

Since heapster is now deprecated and has reached end of life, we will use metrics-server in order to check resource metrics in kubernetes. 

## Install metrics server 

```
git clone https://github.com/kubernetes-incubator/metrics-server
cd metrics-server/deploy
kubectl create -f 1.8+/

```

Ensure that metrics server is running 

```
kubectl get pods -n kube-system | grep metrics
metrics-server-v0.3.1-8d4c5db46-fgb6v                          2/2     Running   0          5m39s
```

## Gather Pod Resource metrics

```
kubectl top pods --all-namespaces

NAMESPACE     NAME                                                           CPU(cores)   MEMORY(bytes)
kube-system   event-exporter-v0.2.5-7df89f4b8f-gtrzv                         1m           18Mi
kube-system   fluentd-gcp-scaler-54ccb89d5-8mjr6                             0m           31Mi
kube-system   fluentd-gcp-v3.1.1-ksthc                                       10m          141Mi
kube-system   fluentd-gcp-v3.1.1-r7gch                                       6m           139Mi
kube-system   heapster-696599ddd4-kh2dt                                      1m           34Mi
kube-system   kube-dns-5877696fb4-sgz57                                      2m           30Mi
kube-system   kube-dns-5877696fb4-xvn8d                                      2m           30Mi
kube-system   kube-dns-autoscaler-85f8bdb54-mt25z                            1m           4Mi
kube-system   kube-proxy-gke-standard-cluster-1-default-pool-410b975c-7jpk   1m           12Mi
kube-system   kube-proxy-gke-standard-cluster-1-default-pool-410b975c-xt8b   1m           12Mi
kube-system   l7-default-backend-8f479dd9-blgzg                              1m           1Mi
kube-system   metrics-server-v0.3.1-8d4c5db46-fgb6v                          1m           17Mi
kube-system   prometheus-to-sd-5v2sp                                         1m           14Mi
kube-system   prometheus-to-sd-qp4s2                                         1m           15Mi
kube-system   stackdriver-metadata-agent-cluster-level-8688665b4f-gg796      3m           19Mi
```

To see the CPU and memory usage for the individual containers of a pod - 

```
kubectl top pod metrics-server-v0.3.1-8d4c5db46-fgb6v -n kube-system --containers

POD                                     NAME                   CPU(cores)   MEMORY(bytes)
metrics-server-v0.3.1-8d4c5db46-fgb6v   metrics-server-nanny   1m           4Mi
metrics-server-v0.3.1-8d4c5db46-fgb6v   metrics-server         1m           12Mi
```

## Gather Node resource metrics 

```
kubectl top node

NAME                                                CPU(cores)   CPU%   MEMORY(bytes)   MEMORY%
gke-standard-cluster-1-default-pool-410b975c-7jpk   40m          4%     570Mi           21%
gke-standard-cluster-1-default-pool-410b975c-xt8b   69m          7%     764Mi           28%
```

We can also see the CPU and memory usage for individual nodes by specifying a node name

```
kubectl top node gke-standard-cluster-1-default-pool-410b975c-7jpk 

NAME                                                CPU(cores)   CPU%   MEMORY(bytes)   MEMORY%
gke-standard-cluster-1-default-pool-410b975c-7jpk   46m          4%     571Mi           21%

```


