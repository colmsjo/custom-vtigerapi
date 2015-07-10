Custom vTiger api1
==================

Pre-requisites
--------------

 * A [vtiger image](https://clabvtigerdev.gizur.com/vtigercrm) for send api request
 * A memcache server is needed (https://github.com/gizur/beservices)
 * A Postfix server is needed to send Email (https://github.com/gizur/beservices)
 * Amazon S3 Bucket is needed to save images (Using Aws api)


Setup
-----------------

1. Clone this repo into a [LAMP container](https://github.com/colmsjo/docker-lamp).
See the README of the LAMP container for instructions.

2. Setup the environment
using `env.list` in supervisor (`/etc/supervisor/conf.d/supervisord.conf `).
Don't forget to do: `supervisorctl update`.
Check http://[IP]:[PORT]/info.php for the environment
variables.

3. Run `./setup.sh`


Update main.config
------------------

* Change memcache server detail
* Change vTiger api path
* Change Postfix Server Configuration


Tests
----

see [`test`](test/README.md)


Send request using curl
-------------------------

0. Prepare credentials:
  username=<portal username>
  password=<portal password>

1. Login Request
  curl -X POST http://localhost/api/Authenticate/login \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

2. Assets List Request
  curl -X GET http://localhost/api/Assets \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

3. For trailer app portal: to get all damages we not use custom and vtiger api, For same we use direct mysql connection.


4. For Assets tab
  curl -X GET http://localhost/api/Assets/Search/s?searchString='' \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

5. Get All Account
  curl -X GET http://localhost/api/Accounts \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

6. Get All Products
  curl -X GET http://localhost/api/Products \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

7. Get Pick list
  curl -X GET http://localhost/api/Assets/trailertype \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

8. Get All Products
  curl -X GET http://localhost/api/Assets/trailertype \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

9. Create new Assets
  curl -X POST http://localhost/api/Assets \
  -H "Content-Type:application/json" -H "X-USERNAME:$username" \
  -H "X-PASSWORD:$password" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"
  -D data
