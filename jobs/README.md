# JOBS and CRONJOBS

##  JOBS

> Jobs are specialized controllers. Just like any other controller (Replication Controller, ReplicaSet, or Deployments), they control the state of the underlying pods. Jobs will run a pod to completion - it means that job will create a pod, run it for a finite amount of time and place the pod in **Completed** state after the execution is done. 

Jobs are the perfect usecase for - 

* Batch Processing
* Big Data transformation jobs (Mapreduce / GCP Dataflow ) 
* Creating a setup job like an Operator like Mysql/Spark Operator 
* ML processing (Image processing / NLP)

##  Why Jobs ? 

> In order to understand why do you need jobs - lets do a quick demo to run a pod that adds two numbers - 

` kubectl create -f pod.yaml`

```
kubectl create -f pod.yaml 
pod/pod-math created
```

Observations : 

```
kubectl get pods 
NAME       READY   STATUS      RESTARTS   AGE
pod-math   0/1     Completed   2          22s
```

> Check the restarts - everytime a pod completes, it restarts everytime and performs the same action again and again.

> Jobs take advantage of the restartPolicy feature which is set to **Never** or **onFailure** which states that a pod will never restart or restart only when there is an underlying failure. 

> Lets create a job - 

` kubectl create -f job.yaml`

```
kubectl get jobs 
NAME       COMPLETIONS   DURATION   AGE
math-job   1/1           3s         47s

kubectl get pods 
NAME             READY   STATUS      RESTARTS   AGE
math-job-pr8ks   0/1     Completed   0          52s
```

Observations: 

> The job has executed the pod in Completed status and the Restarts remain as 0. 

##  Types of Jobs 

There are three major types of jobs - 

* Jobs with a single completion count 

> By default the completion count of a Job is set as 1. A job which runs one single pod and executes to completion is a job with a single completion count. 

* Jobs with fixed completion count

> A job can have a completion count set. If a completion count is set, the job will create one or more set of pods **sequentially** and execute the same set of task. Each pod will wait for its predeccesor pod to complete before it can start. 

> Lets do a demo by creating a job with completion count as 3 to perform prime number search between a range of numbers - 

` kubectl create -f jobs-completion-count-prime.yaml`

```
kubectl get pods 
NAME                    READY   STATUS      RESTARTS   AGE
primes-parallel-h6cv5   1/1     Running     0          9s

kubectl get pods 
NAME                    READY   STATUS              RESTARTS   AGE
primes-parallel-4ztql   0/1     ContainerCreating   0          1s
primes-parallel-6b8cp   0/1     Completed           0          16s
primes-parallel-h6cv5   0/1     Completed           0          33s
```

Observations - 

> Specifying the count as 3 - creates 3 pods sequentially. The output of all the pods are exactly the same as it is performing the same set of tasks. This kind of job is useful when working with a streaming service like Kafka / Pub-Sub etc which streams data continuously and the job will spawn new pods to take in new workload. 


* Jobs with parallelism 

> In contrast to fixed completion count, a job with parallelism will deploy the specified number of pods in parallel to perform parallel batch processing. You can specify the spec.parallelism count as the number of parallel pods you want to run. 

> Lets do a demo by creating a job with parallelism that calculates the value of pi 

` kubectl create -f jobs-parallelism.yaml`

```
kubectl get pods 
NAME                READY   STATUS              RESTARTS   AGE
example-job-cxng2   0/1     ContainerCreating   0          2s
example-job-fh82k   0/1     ContainerCreating   0          2s
example-job-rlts8   0/1     ContainerCreating   0          2s
example-job-sm9gw   0/1     ContainerCreating   0          2s
example-job-tnrpb   0/1     ContainerCreating   0          2s
```

Observations - 

> 5 parallel pods are created that have processed the same task of calculating the value of pi. A good use case of using parallelism is when you are working with a Queue like redis/rabbitMQ or running an automated build using a kubernetes deployment of jenkins. 


##  Cronjobs

> Cronjobs are used to create a time based schedule for jobs. It utilizes the cron format to create a schedule and will schedule pods accordingly. Cronjobs are a good usecase when working with metrics/monitoring system where you want a scheduled checks on your system. 

> The crom format is as below - 

~~~
# ┌───────────── minute (0 - 59)
# │ ┌───────────── hour (0 - 23)
# │ │ ┌───────────── day of the month (1 - 31)
# │ │ │ ┌───────────── month (1 - 12)
# │ │ │ │ ┌───────────── day of the week (0 - 6) (Sunday to Saturday;
# │ │ │ │ │                                   7 is also Sunday on some systems)
# │ │ │ │ │
# │ │ │ │ │
# * * * * * command to execute
~~~

> Lets do a demo by creating a cronjob that echos "Hello World" every 1 minute - 

` kubectl create -f cronjob.yaml` 

~~~
kubectl get cronjob
NAME         SCHEDULE      SUSPEND   ACTIVE   LAST SCHEDULE   AGE
printhello   */1 * * * *   False     0        <none>          44s

kubectl get pods 
NAME                          READY   STATUS      RESTARTS   AGE
printhello-1558115040-5ndq6   0/1     Completed   0          9s

kubectl get pods 
NAME                          READY   STATUS              RESTARTS   AGE
printhello-1558115040-5ndq6   0/1     Completed           0          60s
printhello-1558115100-m5g4v   0/1     ContainerCreating   0          0s
~~~


Observations:

> As you can see - after 60 seconds, a new pod is scheduled that will perform the same action

##  Advanced demo on Job parallelism 

> As a part of our advanced demo - we will deploy a redis pod and fill the redis pod with some dummy queue. Once the queue is created on redis, we will deploy a job with parallelism to query the same queue. The parallel pods will work with redis to fetch the data from queue parallely. 

` cd parallelism` 

> Deploy the redis pod and service 

` kubectl create -f redis-pod.yaml -f redis-service.yaml `

~~~
kubectl get po,svc
NAME               READY   STATUS    RESTARTS   AGE
pod/redis-master   1/1     Running   0          14s

NAME                 TYPE        CLUSTER-IP       EXTERNAL-IP   PORT(S)    AGE
service/kubernetes   ClusterIP   10.96.0.1        <none>        443/TCP    5d4h
service/redis        ClusterIP   10.103.152.222   <none>        6379/TCP   22h
~~~


> Once the redis pod is created - we will deploy a temporary redis pod and connect it to our deployed redis pod using redis-cli. An alternative would be to install redis-cli on your system and use redis-cli to connect to your redis service. 

` kubectl run -i --tty temp --image redis --command "/bin/sh" `

> Once inside the prompt - connect to our deployed redis service - 

` redis-cli -h redis` 

~~~
redis-cli -h redis
redis:6379> 
~~~

> Add some dummy queue and verify the dummy queue 

~~~
rpush job2 "apple"
rpush job2 "banana"
rpush job2 "cherry"
rpush job2 "date"
rpush job2 "fig"
rpush job2 "grape"
rpush job2 "lemon"
rpush job2 "melon"
rpush job2 "orange"
rpush job2 "strawberry"
rpush job2 "mango"
lrange job2 0 -1
exit
~~~

> The Dockerfile and the corresponding python script to read the Queue is already provided in this repo. Build the dockerfile and push it to your docker registry

` docker build . -t YOUR_REGISTRY_NAME/job-redis`

` docker push YOUR_REGISTRY_NAME/job-redis

> The file `pod-parallelism.yaml` contains the definition of a job that runs 2 parallel counts. This will query the same queue and will distribute the load. 

` kubectl create -f pod-parallelism.yaml`


~~~
kubectl get pods 
NAME                   READY   STATUS    RESTARTS   AGE
job-redis-cr9zt        1/1     Running   0          8s
job-redis-ftjt4        1/1     Running   0          8s
~~~

Observations: 

` Check the logs of both the job. Even though they are running the same workload, the queries from redis is now distributed 

~~~
kubectl logs job-redis-cr9zt
Worker with sessionID: 1162d338-5e55-4332-b245-c473bac606bc
Initial queue state: empty=False
Working on mango
Working on orange
Working on lemon
Working on fig
Working on cherry
Working on apple
Queue empty, exiting


kubectl logs job-redis-ftjt4
Worker with sessionID: 905140d4-ae95-4887-bd9e-11170bd52837
Initial queue state: empty=False
Working on strawberry
Working on melon
Working on grape
Working on date
Working on banana
Waiting for work
Waiting for work
Waiting for work
Queue empty, exiting
~~~























