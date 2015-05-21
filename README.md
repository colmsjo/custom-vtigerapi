Custome Vtiger api
==================

Pre-requisites
--------------

 * A [vtiger image](https://clabvtigerdev.gizur.com/vtigercrm) for send api request
 * A mem cache server is needed (https://github.com/gizur/beservices)
 
Update main.config
------------------
 * Change mem cahce server detail
 * Change Vtiger api path
 
Send request using curl
-------------------------
  1. Login Request
    curl -X POST http://localhost/gizur/api/Authenticate/login 
    -H "Content-Type:application/json" -H "X-USERNAME:niclas.andersson@coop.se" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"
  
  2. Assets List Request
    curl -X GET http://localhost/gizur/api/Assets 
    -H "Content-Type:application/json" -H "X-USERNAME:niclas.andersson@coop.se" 
    -H "X-PASSWORD:123456" -H "X-CLIENTID:clab" -H "ACCEPT-LANGUAGE:en" -H "ACCEPT:json"
    
