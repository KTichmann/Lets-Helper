# Lets-Helper

A letsencrypt helper plugin for WordPress that allows management of ssl certificates from the wordpress frontend. This plugin was built to run on a wordpress multisite installation created with Bitnami.

## Installation Instructions
The plugin uses a shell script in the admin folder - to allow the script to be called from the plugin, the following code needs to be run from the plugins/lets-helper/scripts folder:
$ chmod u+x bon-letsencrypt.sh
$ sudo visudo
This will open a document. Add the following line to the bottom of the file to allow the bon-letsencrypt.sh file to be run as root

daemon ALL = (root) NOPASSWD: /opt/bitnami/apps/wordpress/htdocs/wp-content/plugins/lets-helper/scripts/bon-letsencrypt.sh

## Features

The plugin displays a list of certificates that are active on the multisite. From the menu page, you can add a new certificate (using webroot auth on the server), renew an existing certificate, or revoke a cert. The list of certificate names, domains, and expiry dates is saved to a table in the wp database for quick access on the frontend.

## Screenshots

![screencapture-letsencrypt-ktich-c9users-io-wp-admin-admin-php-2019-04-01-14_52_29](https://user-images.githubusercontent.com/19572974/55329425-d1980180-548e-11e9-9cef-ebc6f0acb9c5.png)

![screencapture-letsencrypt-ktich-c9users-io-wp-admin-admin-php-2019-04-01-14_52_21](https://user-images.githubusercontent.com/19572974/55329394-bdec9b00-548e-11e9-9650-4f426c236d17.png)

![screencapture-letsencrypt-ktich-c9users-io-wp-admin-admin-php-2019-04-01-14_53_32](https://user-images.githubusercontent.com/19572974/55329431-d6f54c00-548e-11e9-9ceb-bcb74ff79de3.png)
