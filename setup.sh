#!/bin/bash

cp -r ./api /var/www/html/api

chmod a+rwx /var/www/html/api/assets /var/www/html/api/protected/data /var/www/html/api/protected/runtime
cp -r ./framework /var/www/html/framework
