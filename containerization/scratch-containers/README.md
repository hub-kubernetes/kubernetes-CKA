# Experimenting with containers from scratch - debian / ubuntu 

##  What is a minimal container? 

>  A minimal container contains the least amount of packages. A rootfs can be treated with the most basic container 

##  What is a rootfs 

>   A root file system contains everything needed to support a full Linux System. rootfs is a type of root file system. 
rootfs is considered to be a minimal root filesystem which is an instance of ramfs. 
Only certian libraries / binaries are loaded with a minimal rootfs. 
It has directories for proc, sys, dev - however a rootfs is not mounted by default.
To use rootfs - you will have to mount /dev /sys to your local /dev and /sys
A simple way of extracting rootfs from initramfs is as below - 
~~~    
cd `mktemp -d` && gzip -dc /boot/initrd.img-`uname -r` | cpio -ivd
~~~
##  Install debootstrap to create rootfs 

    apt-get install debootstrap
    
##  Use debootstrap to create rootfs 

    1.  Debian rootfs

    mkdir rootfs_debian
    
    debootstrap stable rootfs_debian http://deb.debian.org/debian/ 
    
    
    2.  Ubuntu rootfs
    
    mkdir rootfs_ubuntu
    
    debootstrap --arch=amd64 xenial rootfs_ubuntu http://archive.ubuntu.com/ubuntu/ 
    
##  Change root using chroot 

    1. Debian
    
    chroot  rootfs_debian /bin/bash 
    
    mount -t proc proc /proc
    
    2. Ubuntu
    
    chroot rootfs_ubuntu /bin/bash
    
    mount -t proc proc /proc
    
    3.  Unmount proc after playing around 
    
    umount /root/rootfs_ubuntu/proc
    
    umount /root/rootfs_debian/proc
    

##  What is a Linux namespace

    Linux namespaces allow isolation of global system resources between multiple processes.
    The isolation can be on the levels of PID, mounts, IPC, network, user, UTS etc. 
    This isolation is provided by unshare command 
    
    Unshare provides the below flags 
    
    -i  Unshare the IPC namespace.
    
    -m  Unshare the mount namespace.
    
    -n  Unshare the network namespace.
    
    -p  Unshare  the pid namespace.
    
    -u  Unshare the UTS namespace.
    
    -U  Unshare the user namespace.
    
##  Using unshare to create namespaces with chroot 

    1.  PID namespace
    
    unshare -p -f chroot ./rootfs_ubuntu /bin/bash
    
    mount -t proc proc /proc
    
    ps -ef 
    UID        PID  PPID  C STIME TTY          TIME CMD
    root         1     0  0 18:44 ?        00:00:00 /bin/bash
    root        13     1  0 18:44 ?        00:00:00 ps -ef
    
    Run some additional commands inside the container and on host machine - 
    
    ipcs -a -- Same values on both container and local machine
    
    ip addr -- Same values on both container and local machine
    
    2.  Prove why only PID is isolated by comparing namespace details 
    
        a.  Open a new terminal 
        
        b.  ps -ef | grep "/bin/bash" and get PID of the bash spawned by unshare command
        
        c.  ls -l /proc/{{PID}}/ns
        
        d.  Take any existing process and get its PID 
        
        e.  ls -l /proc/{{PID}}/ns 
        
        f.  Compare values for all namespaces 
        
    3.  A more complex unshare 
    
        unshare -p -i -n -u -f chroot ./rootfs_ubuntu /bin/bash
        
        Verify - 
        
        ipcs -a 
        
        ip addr 
        

##  Using cgroups to assign resources 

    1.  /sys/fs/cgroup/ - provides multiple resources that can be attached to your containers 
    
    2.  mkdir /sys/fs/cgroup/memory/memory_ubuntu
    
    3.  Automatic assignment of some files - 
    
        ls -ltra /sys/fs/cgroup/memory/memory_ubuntu
        
    4.  Add 1 MB memory to memory_ubuntu
    
    vi memory.limit_in_bytes
    
    Add the value - 1000000 
    
    This is nearly 1 MB
    
    5. On a separate terminal - unshare -p -i -n -u -f chroot ./rootfs_ubuntu /bin/bash
    
    6.  ps -ef | grep "/bin/bash" -- get the PID of the above container 
    
    7.  echo {{PID}} > tasks 
    
    8.  tasks file assigns this cgroup to the container 
    
    9. Perform some actions till all actions are getting killed due to memory issue 
    
    10. exit the container - notice that tasks and memory files are refreshed to an older state 
    
    
    
        
        
        
        
    
  
  
  
    
    
