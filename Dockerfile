# Supervisord, PHP, Python, MySQL
#
# VERSION               0.0.1
#

FROM     ubuntu:trusty
MAINTAINER Jonas Colmsjö "jonas@gizur.com"

RUN echo "export HOME=/root" >> /root/.profile

# Mirros: http://ftp.acc.umu.se/ubuntu/ http://us.archive.ubuntu.com/ubuntu/
RUN echo "deb http://ftp.acc.umu.se/ubuntu/ trusty-updates main restricted" >> /etc/apt/source.list
RUN apt-get update

RUN apt-get install -y wget nano curl git


#
# Install supervisord (used to handle processes)
# ----------------------------------------------
#
# Installation with easy_install is more reliable. apt-get don't always work.

RUN apt-get install -y python python-setuptools
RUN easy_install supervisor

ADD ./etc-supervisord.conf /etc/supervisord.conf
ADD ./etc-supervisor-conf.d-supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/log/supervisor/


#
# Install Apache
# ---------------

RUN apt-get install -y apache2
RUN a2enmod rewrite
ADD ./etc-apache2-apache2.conf /etc/apache2/apache2.conf


RUN rm /var/www/html/index.html
RUN echo "<?php\nphpinfo();\n " > /var/www/html/info.php


#
# Use phpfarm to manage PHP versions
# ----------------------------------
#
# Add one script per PHP version and update

# Preparations
RUN apt-get update
RUN apt-get install -y php5
RUN a2enmod php5


RUN apt-get update
RUN apt-get install -y php5-memcache
RUN php5enmod memcache

RUN apt-get update
RUN apt-get install -y php5-mcrypt
RUN php5enmod mcrypt


RUN apt-get update
RUN sudo apt-get install -y php5-redis
RUN php5enmod redis


RUN apt-get update
RUN sudo apt-get install -y php5-curl
RUN php5enmod curl


RUN apt-get update
RUN sudo apt-get install -y php5-mysql
RUN php5enmod mysql



#
# Add MySQL db dump
# -----------------

RUN apt-get install -y mysql-client-5.6


#
# Install rsyslog
# ---------------

RUN apt-get install -y rsyslog

ADD ./etc-rsyslog.conf /etc/rsyslog.conf
ADD ./etc-rsyslog.d-50-default.conf /etc/rsyslog.d/50-default.conf



# Install PHP apps
# ----------------

ADD ./api /var/www/html/api

RUN chmod a+rwx /var/www/html/api/assets /var/www/html/api/protected/data /var/www/html/api/protected/runtime
ADD ./framework /var/www/html/framework

# Install tests
ADD ./test /test


#
# Start apache and mysql using supervisord
# -----------------------------------------

# Fix permissions
RUN chown -R www-data:www-data /var/www/html



EXPOSE 80 443
CMD ["supervisord"]
