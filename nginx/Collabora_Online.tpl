#
# Serve our NodeJS based app at the base url
#

server {
    listen      %ip%:%proxy_port%;
    server_name %domain_idn% %alias_idn%;

    include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

    location /error/ {
        alias   %home%/%user%/web/%domain%/document_errors/;
    }

    location ~ /\.(?!well-known\/|file) {
       deny all;
       return 404;
    }
    
    # static files
    location ^~ /browser {
        proxy_pass http://127.0.0.1:9990;
        proxy_set_header Host $http_host;
    }

    # WOPI discovery URL
    location ^~ /hosting {
        proxy_pass http://127.0.0.1:9990;
        proxy_set_header Host $http_host;
    }

    # main websocket
    location ~ ^/cool/(.*)/ws$ {
        proxy_pass http://127.0.0.1:9990;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $http_host;
        proxy_read_timeout 36000s;
    }
   
    # download, presentation and image upload
    location ~ ^/cool {
        proxy_pass http://127.0.0.1:9990;
        proxy_set_header Host $http_host;
    }
   
    # Admin Console websocket
    location ^~ /cool/adminws {
        proxy_pass http://127.0.0.1:9990;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $http_host;
        proxy_read_timeout 36000s;
    }
   
    # Capabilities
    location ^~ /hosting/capabilities {
        proxy_pass http://127.0.0.1:9990;
        proxy_set_header Host $http_host;
    }

    include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
