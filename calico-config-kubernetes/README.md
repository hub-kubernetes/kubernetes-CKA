When using Calico IPAM, each workload is assigned an address from the selection of configured IP pools. You may want to modify the CIDR of the IP pool of a running cluster for one of the following reasons:

* To move to a larger CIDR that can accommodate more workloads.
* To move off of a CIDR that was used accidentally.


While Calico supports changing IP pools, not all orchestrators do. Be sure to consult the documentation of the orchestrator you are using to ensure it supports changing the workload CIDR.

For example, in Kubernetes, all three of the following arguments must be equal to, or contain, the Calico IP pool CIDRs:

kube-apiserver: `--pod-network-cidr`
kube-proxy: `--cluster-cidr`
kube-controller-manager: `--cluster-cidr`

Removing an IP pool without following this migration procedure can cause network connectivity disruptions in any running workloads with addresses from that IP pool. Namely:

* If IP-in-IP or VXLAN was enabled on the IP pool, those workloads will no longer have their traffic encapsulated.
* If nat-outgoing was enabled on the IP pool, those workloads will no longer have their traffic NAT’d.
* If using Calico BGP routing, routes to pods will no longer be aggregated.

In this example, we created a cluster with kubeadm. We wanted the pods to use IPs in the range 10.0.0.0/16 so we set --pod-network-cidr=10.0.0.0/16 when running kubeadm init. However, we installed Calico without setting the default IP pool to match. Running calicoctl get ippool -o wide shows Calico created its default IP pool of 192.168.0.0/16:

```
NAME                  CIDR             NAT    IPIPMODE   VXLANMODE   DISABLED
default-ipv4-ippool   192.168.0.0/16   true   Always     Never       false

NAMESPACE     WORKLOAD                   NODE      NETWORKS            INTERFACE
kube-system   kube-dns-6f4fd4bdf-8q7zp   vagrant   192.168.52.130/32   cali800a63073ed
```

* Add a new IP pool:

```
calicoctl create -f -<<EOF
apiVersion: projectcalico.org/v3
kind: IPPool
metadata:
  name: new-pool
spec:
  cidr: 10.0.0.0/16
  ipipMode: Always
  natOutgoing: true
EOF

```

Verify using - `calicoctl get ippool -o wide`

* Disable the old IP pool

```
calicoctl get ippool -o yaml > pool.yaml

```

Edit the file pool.yaml - adding `disabled: true` to the `default-ipv4-ippool` IP pool. Apply the config using `calicoctl apply -f pool.yaml`

* Recreate all existing workload

```
kubectl delete pod -n kube-system kube-dns-6f4fd4bdf-8q7zp

```

* Delete the old IP pool

```
calicoctl delete pool default-ipv4-ippool

```
