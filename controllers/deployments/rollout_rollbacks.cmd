kubectl rollout status deployment/mysqldeployment
kubectl rollout history deployment/mysqldeployment
kubectl rollout history deployment/mysqldeployment --revision=2
kubectl rollout undo deployment/mysqldeployment --to-revision=1
