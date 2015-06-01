Custome Vtiger api
==================

Pre-requisites
--------------

 * A [vtiger image](https://clabvtigerdev.gizur.com/vtigercrm) for send api request
 * A mem cache server is needed (https://github.com/gizur/beservices)
 * A Post fix server is needed to send Email (https://github.com/gizur/beservices)
 * Amazon S3 Bucket is needed to save images (Using Aws api)
 
Update main.config
------------------
 * Change mem cache server detail
 * Change Vtiger api path
 * Change Post fix Server Configuration

For Docker
-----------------
 * docker build --rm -t api . Add the flag --no-cache=true if you want to be 100% sure to build from scratch.
 * Start api container:

docker run -t -i -p 8080:80 --env-file=env.list --restart="on-failure:10" \
--link vtiger:vtiger \
--link beservices:beservices -h api --name api api \
/bin/bash -c "supervisord; export > /env; bash"

 * Disconnect with ctrl-p ctrl-q.
 * The logs are available in logstash in the beservices container.
 
Send request using curl
-------------------------
  1. Login Request
    curl -X POST http://localhost/gizur/api/Authenticate/login 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"
  
  2. Assets List Request
    curl -X GET http://localhost/gizur/api/Assets 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

  3. For trailer app portal: to get all damages we not use custom and vtiger api, For same we use direct mysql connection. 


  4. For Assets tab
    curl -X GET http://localhost/gizur/api/Assets/Search/s?searchString='' 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"
    
 5. Get All Account
    curl -X GET http://localhost/gizur/api/Accounts 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

 6. Get All Products
    curl -X GET http://localhost/gizur/api/Products 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

 7. Get Pick list
    curl -X GET http://localhost/gizur/api/Assets/trailertype 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

 8. Get All Products
    curl -X GET http://localhost/gizur/api/Assets/trailertype 
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"

 9. Create new Assets
    curl -X POST http://localhost/gizur/api/Assets
    -H "Content-Type:application/json" -H "X-USERNAME:<username>" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json" 
    -D data



