FROM nginx:alpine

# Remove the default nginx configuration file
RUN rm /etc/nginx/conf.d/default.conf

COPY docker/dev/nginx/conf/nginx.conf      /etc/nginx/nginx.conf

RUN adduser -u 1000 -D -S -G www-data www-data

EXPOSE 80
