#!/bin/bash

curl -sS https://getcomposer.org/installer | php
mv composer.phar composer
./composer install

