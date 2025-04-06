FROM nginx:1.23.3-alpine

RUN rm /etc/nginx/conf.d/default.conf
COPY ./sites/* /etc/nginx/conf.d
