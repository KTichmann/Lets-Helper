#!/bin/bash

if [ $1 == 'certificates' ]; then
  cd /
  sudo ./certbot-auto certificates
fi

if [ $1 == 'createdry' ]; then
  cd /
  sudo ./certbot-auto certonly --webroot --staging --dry-run -w /opt/bitnami/apps/wordpress/htdocs -d $2 -d $3
fi

if [ $1 == 'createactual' ]; then
  cd /
  #Run certbot to create the certificate
  echo "1" | ./certbot-auto certonly --webroot -w /opt/bitnami/apps/wordpress/htdocs -d $2 -d $3
fi

if [ $1 == 'changeemail' ]; then
  cd /
  ./certbot-auto register --update-registration --email $2 --non-interactive
fi

if [ $1 == 'updatevhosts' ]; then
    cd /
    echo "#$2
  <VirtualHost *:80>
        ServerName $2
        ServerAlias *.$2
        DocumentRoot \"/opt/bitnami/apps/wordpress/htdocs\"
        Include \"/opt/bitnami/apps/wordpress/conf/httpd-app.conf\"
  </VirtualHost>
  <VirtualHost *:443>
        SSLEngine on
        DocumentRoot \"/opt/bitnami/apps/wordpress/htdocs\"
        ServerName $2
        ServerAlias *.$2
        SSLCertificateFile \"/etc/letsencrypt/live/$2/cert.pem\"
        SSLCertificateKeyFile \"/etc/letsencrypt/live/$2/privkey.pem\"
        SSLCertificateChainFile \"/etc/letsencrypt/live/$2/fullchain.pem\"
        Include \"/opt/bitnami/apps/wordpress/conf/httpd-app.conf\"
  </VirtualHost>
  #end" >> /opt/bitnami/apps/wordpress/conf/httpd-vhosts.conf

  # gsutil rsync -p -c -r -d /etc/letsencrypt gs://main-bonline-518506938661-wp-data/app-certificates
  # gsutil rsync -p -c -r -d /opt/bitnami/apps/wordpress/conf gs://main-bonline-518506938661-wp-data/app-vhosts
fi

if [ $1 == "revokecert" ]
then
  
  cd /
  echo "Y" | ./certbot-auto revoke --cert-path /etc/letsencrypt/live/$2/cert.pem --key-path /etc/letsencrypt/live/$2/privkey.pem

  #remove virtual-hosts from httpd-vhosts.conf file
  sed -i /opt/bitnami/apps/wordpress/conf/httpd-vhosts.conf -re "/#$2/, /#end/d"

fi