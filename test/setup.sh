#!/bin/bash

cd /test
ln -s /opt/phpfarm/inst/bin/php-5.3.27  /usr/local/bin/php
curl -sS https://getcomposer.org/installer | php
mv composer.phar composer
./composer install

