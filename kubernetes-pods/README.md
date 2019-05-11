# Pod Design Patterns 

> A pod is the must fundamental unit of deployment in kubernetes. A pod contains either a single container or multiple containers. In most cases a single container pod would be the answer to your solution. However, if you want to embed multiple containers within the same pod, there are some design considerations that matches multiple use-cases. Use these considerations wisely. For example, its not recommended or advisable to have a webserver and a database container within a single pod, even though, it might lead to faster responses. One good use-case of using a multicontainer pod is if you have a proxy service that runs right before your API. 

> Its important to note that the functionality of your application doesnot change in either cases, i.e. if you deploy a multicontainer pod or if you just deploy individual pods. Multicontainer pods are used for simpler communication between closely coupled applications. Since containers within the same pod share the same **user-space**, it becomes easier for two containers to interact with each other using volumes, queues, semaphores etc. 

Below are some examples of multicontainer pod design patterns 

##  SideCar pattern

> In this pattern, you deploy your primary APP container and one or more non-app containers. These non-app containers doesnt provide any significant enhancement to your primary application. These additional containers can be logging agents(logstash), monitoring agent (appdymanics) or any custom watcher or network sniffer containers. Since these containers share the same volumes, the non-app container can fetch data written by your app container and can be used to send these data to backend storages (persistent volumes, elastic search, stackdriver, datadog, etc ) 

##  Adapter pattern 

> This patters is used when you want to transform the output or data of your primary app containers before the data is actually utilized by your backend services. Adapter containers are used along with multiple web-services like nginx, apache which outputs the logs in a standard format. The adapter container can take the raw data from the web-server logs and perform selective data transfomation on top of these logs to create a standardize output file like csv or json when can then be used by your backend systems. 

##  Ambassador pattern

> When designing a distributed appliction it is essential to understand that any communication of an application or microservice to any other microservice or to the external world is goverened by endpoints. In short - a microservice application only needs the endpoint details to talk to the rest of the world. In ambassador pattern, a proxy application is deployed along with the primary app. The primary app takes care of performing the workload tasks and the proxy containers takes care of providing endpoints for the primary app. The concept of service sharding is one of the most basic and primary usecase of ambassador pattern. Envoy is an example of a proxy container that runs as sidecar to your main app to provide service endpoints to other applicatons. Another good usecase is when you deploy in-memory caching applications like redis or memcache with your primary app. These memory-cache sidecars can interact locally with your application for faster caching. They can then connect to the corresponding masters/sentinel applications externally.
