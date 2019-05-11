# Docker Demo 

##  Build Base image 

    1.  Download the repository
    
    2.  alpine-minirootfs-3.9.2-x86_64.tar - This is the rootfs for alpine
    
    3.  Create the base image 
    
    docker import alpine-minirootfs-3.9.2-x86_64.tar alpine-baseimage
    
    4.  docker images
    
        REPOSITORY          TAG                 IMAGE ID            CREATED             SIZE
        
        alpine-baseimage    latest              0c0b4476ca3e        17 seconds ago      5.53MB
        
    5.  Test the image 
    
    docker run -d -it alpine-baseimage sh
    
    docker ps 
    
    docker exec -it {{ container-id }} sh 
    
##  Utilize Base Image to create a new nginx image

    1.  Create a dockerfile using alpine-baseimage (attached in repository)
    
    2.  Install nginx and upload the configurations from default.conf and index.html file 
    
    3.  Build the image 
    
    docker build . -t nginx-alpine 
    
    4.  Test the image 
    
    docker run --name nginx -d -p 8080:80 nginx-alpine
    
    curl localhost:8080 -- THis should give the output of the index.html file 
    
    
    
    
    
