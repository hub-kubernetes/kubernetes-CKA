FROM alpine-baseimage
Maintainer "Harshal Sharma"
RUN apk update && apk upgrade 
RUN apk add openrc --no-cache 
RUN apk add nginx && mkdir -p /usr/share/nginx/html && mkdir -p /run/nginx
COPY default.conf /etc/nginx/conf.d/default.conf 
COPY index.html /usr/share/nginx/html/index.html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
