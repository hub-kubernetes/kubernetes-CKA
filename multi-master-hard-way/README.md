# Multi Master Demo Using Kubernetes the Hard Way 

##  Pre-requisite 

*     Master Node 1 - 2 cpu x 4 GB 
*     Master Node 2 - 2 cpu x 4 GB 
*     Worker Node 1 - 2 cpu x 4 GB 
*     Worker Node 2 - 2 cpu x 4 GB 
*     kubectl Node  - 1 cpu x 2 GB 
*     LoadBalancer  - 1 cpu x 2 GB


# Install necessary software on kubectl node

##  What are SSL certificates ?

> SSL certificate enables encrypted transfer of sensitive information between a client and a server. The purpose of encryption is to make sure that only the intended recipient will be able to view the information. SSL certificates are used to enable https connection between browser and websites.

##  How to generate SSL certificates ?

> There are multiple toolkits available in the market to create self signed SSL certificates. Most notable of them are - 

*  openssl
*  cfssl
*  easyrsa 

> Self signed certificates are useful when you want to enable SSL/TLS envryption for applications that run within your organization. These certificates are not recognized by browsers as the certificate authority is internal to your organization itself. In order to enable communication with any system outside your organization, you will have to set up MSSL/2 way SSL. 

> There are multiple third party SSL certificate providers like verisign, symantec, intouch etc. Their Certificate Authority is embedded with all major browsers like chrome, IE, safari, mozilla. This enables any external user to connect to your server using a secure HTTPS connection that is recognized by the browser.  

##  Components of SSL certificate 

##  Certificate Authority (CA)

> 



















