<?php

/**
 * PHP Unit Test cases class. Testin Gizur API
 * 
 * PHP version 5
 * 
 * @category   Test
 * @package    Gizur
 * @subpackage Test
 * @author     Anshuk Kumar <anshuk-kumar@essindia.co.in>
 * @copyright  2012 &copy; Gizur AB
 * @license    Gizur Private Licence
 * @version    0.2
 * @link       http://www.gizur.com
 */

/**
 * Unit Test class for Testing the Gizur REST API ( wrapper over 
 * vtiger REST API )
 * Contains methods which test  
 * Login / authentication, view details of an asset, list category based
 * trouble tickets and create a trouble ticket
 * 
 * Testing method:
 * > phpunit --verbrose Gizur_REST_API_Test
 */

require_once realpath(__DIR__ . '/config.inc.php');
require_once 'PHPUnit/Autoload.php';
require_once realpath(__DIR__ . '/lib/RESTClient.php');
require_once realpath(__DIR__ . '/../lib/aws-php-sdk/sdk.class.php');

/**
 * Gizur Test inherist PHP Unit Tests Framework
 * 
 * @category  Test
 * @package   Gizur 
 * @author    Anshuk Kumar <anshuk-kumar@essindia.co.in>
 * @copyright 2012 &copy; Gizur AB
 * @license   Gizur Private Licence
 * @version   0.2
 * @link      http://www.gizur.com
 */

class Girur_REST_API_Test extends PHPUnit_Framework_TestCase
{
    private $_gizurCloudSecretKey = "";
    private $_gizurCloudApiKey = "";

    private $_apiVersion = "0.1";
    
    private $_rest;

    private $_credentials = Array();

    private $_url = <<<URL
"http://phpapplications-env-sixmtjkbzs.elasticbeanstalk.com/api/"
URL;

    /**
     * Generates Signature for request
     * 
     * @param string $method      The Http method used to send the request
     * @param string $model       Model which is being accessed
     * @param string $timestamp   Time of the format date("c")
     * @param string $uniqueSalt Any unique string 
     * 
     * @return string signature
     */
    private function _generateSignature($method, $model, $timestamp, 
        $uniqueSalt
    ) 
    {
        //Build array
        $params = array(
            'Verb'          => $method,
            'Model'         => $model,
            'Version'       => $this->_apiVersion,
            'Timestamp'     => $timestamp,
            'KeyID'         => $this->_gizurCloudApiKey,
            'UniqueSalt'    => $uniqueSalt
        );
        
        // Sorg arguments
        ksort($params);

        // Generate string for sign
        $stringToSign = "";
        foreach ($params as $k => $v)
            $stringToSign .= "{$k}{$v}";   
        //echo PHP_EOL . $stringToSign;
                 
        // Generate signature
        $signature = base64_encode(
            hash_hmac('SHA256', $stringToSign, $this->_gizurcloudSecretKey, 1)
        );    
        
        return array($params, $signature);
    }
    
    /**
     * Sets the header from for CURL
     * 
     * @param string $username  string to be set to HTTP_X_USERNAME header
     * @param string $password  string to be set to HTTP_X_USERNAME header
     * @param string $params    string to be set to HTTP_X_USERNAME header
     * @param string $signature string to be set to HTTP_X_USERNAME header
     * 
     * @return string signature
     */    
    
    private function _setHeader($username, $password, $params, $signature)
    {
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        $this->_rest->set_header('X_TIMESTAMP', $params['Timestamp']);
        $this->_rest->set_header('X_SIGNATURE', $signature);                   
        $this->_rest->set_header(
            'X_GIZURCLOUD_API_KEY', $this->_gizurCloudApiKey
        );
        $this->_rest->set_header('X_UNIQUE_SALT', $params['UniqueSalt']);
    }
    
    /**
     * Executed before every Test case
     * 
     * @return void
     */      
    
    protected function setUp()
    {
        $this->_rest = new RESTClient();
        $this->_rest->format('json'); 
        $this->_rest->ssl(false);
        $this->_rest->language(array('en-us;q=0.5','sv'));        
        $config = new Configuration();
        $configuration = $config->get();

        $this->_url = $configuration['url'];
        $this->_gizurCloudApiKey = $configuration['GIZURCLOUD_API_KEY'];
        $this->_gizurcloudSecretKey = $configuration['GIZURCLOUD_SECRET_KEY'];
        $this->_apiVersion = $configuration['API_VERSION'];
        $this->_credentials = $configuration['credentials'];
        
    }
    
    /**
     * Executed after every Test case
     * 
     * @return void
     */        
    
    protected function tearDown()
    {
        echo PHP_EOL . PHP_EOL;
    }
    
    /**
     * Tests the Login of the API multiple times
     * 
     * @return void
     */ 
    
    public function testLoginStress()
    {
        //Request parameters
        $model = 'Authenticate';
        $action = 'login';
        $method = 'POST';
        $delta = 0;
        $times = 100;
        
        $this->markTestSkipped('');

        echo "Authenticating Login " . PHP_EOL;        
        ob_flush();
        
        for($i=0;$i<$times;$i++)
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {
            
            //Create REST handle
            $this->setUp();            

            // Generate signature
            list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), 
                uniqid()
            );
            
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);  
            
            echo PHP_EOL . " Attempt No: $i Response: " . 
                $response = $this->_rest->post(
                    $this->_url.$model."/".$action
                );
            
            $response = json_decode($response);
            if ($response->success == false) {
                if ($delta == 0) {
                    if ($response->error->code == 'TIME_NOT_IN_SYNC') {
                        $delta = $response->error->time_difference;
                    } 
                } else {
                    echo PHP_EOL . " Delta Used " . $delta;
                }
            }

            //check if response is valid
            if (isset($response->success)) {
                //echo json_encode($response) . PHP_EOL;
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
            ob_flush();
        }
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests the Login of the API single times
     * 
     * @return void
     */     
    
    public function testLoginSingle()
    {
        //Request parameters
        $model = 'Authenticate';
        $action = 'login';
        $method = 'POST';
        $delta = 0;
        
        
        echo " Authenticating Login " . PHP_EOL;        
  
        //set credentials
        $this->_credentials += Array(
            'user1' => 'password1',
            'user2' => 'password2',
            'user3' => 'password3',
            'user4' => 'password4',
            'test@test.com' => '123456'
        );
        
        $validCredentials = Array(
            'user1' => false,
            'user2' => false,
            'user3' => false,
            'user4' => false,
            'cloud3@gizur.com' => true,
            'test@test.com' => false,
            'anil-singh@essindia.co.in' => true,
            'mobile_app@gizur.com' => true,
            'mobile_user@gizur.com' => true,
            'portal_user@gizur.com' => true,
            'jonas.colmsjo@gizur.com' => true,
            'demo@gizur.com' => true
        );        

        Restart:
        
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {  

            //Create REST handle
            $this->setUp();            

            // Generate signature
            list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), 
                uniqid()
            );
            
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);  
            
            echo PHP_EOL . " Response: " . $response = $this->_rest->post(
                $this->_url.$model."/".$action
            );
            
            $response = json_decode($response);
            if ($response->success == false) {
                if ($delta == 0) {
                    if ($response->error->code == 'TIME_NOT_IN_SYNC') {
                        $delta = $response->error->time_difference;
                        goto Restart;
                    } 
                } else {
                    echo PHP_EOL . " Delta Used " . $delta;
                }
            }

            //check if response is valid
            if (isset($response->success)) {
                //echo json_encode($response) . PHP_EOL;
                $this->assertEquals(
                    $response->success, $validCredentials[$username], 
                    " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests the Logout 
     * 
     * @return void
     */     
    
    public function testLogout()
    {
        //Request parameters
        $model = 'Authenticate';
        $action = 'logout';
        $method = 'POST';
           
        echo " Authenticating Logout " . PHP_EOL;        

        //Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );
        
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {    
            
            //Create REST handle
            $this->setUp();
            
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
            
            //Show the Response
            echo PHP_EOL . " Response: " . $response = $this->_rest->post(
                $this->_url.$model."/".$action
            );
            
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                //echo json_encode($response) . PHP_EOL;
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests cron fucntionality for
     * 1) Mailscan
     * 
     * @return void
     */     
    
    public function testCron()
    {
        //Request parameters
        $model = 'Cron';
        $action = 'dbbackup';
        $method = 'PUT';
           
        echo " Executing Cron Mailscan " . PHP_EOL;        

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );
        
        //Set Header
        $this->_setHeader('', '', $params, $signature);
        
        echo PHP_EOL . " Response:  " . $response = $this->_rest->put(
            $this->_url.$model."/".$action
        );
        
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests to fetch the About page
     * 
     * @return void
     */     
    
    public function testAbout()
    {
        //Request parameters
        $model = 'About';
        $method = 'GET';
        
        echo " Fetching About " . PHP_EOL;        

        // Generate signature
        
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );
        
        
        //$this->_rest->set_header('X_USERNAME', $username);
        //$this->_rest->set_header('X_PASSWORD', $password);
        //$this->_rest->set_header('X_TIMESTAMP', $params['Timestamp']);
        //$this->_rest->set_header('X_SIGNATURE', $signature);
        //$this->_rest->set_header(
        //'X_GIZURCLOUD_API_KEY', $this->gizurCloudApiKey
        //);
        $this->_rest->set_header('X_UNIQUE_SALT', $params['UniqueSalt']);
        $this->_rest->format('json');  
        echo PHP_EOL . " Response:  " . $response = $this->_rest->get(
            $this->_url.$model
        );
        
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests the Change password
     * 
     * @return void
     */     
    
    public function testChangePassword()
    {        
        //Request parameters
        $model = 'Authenticate';
        $action = 'changepw';
        $method = 'PUT';
        //$newpassword = 'dddddd';
        //$newpassword = 'ipjibl0f';
           
        //Label the Test
        echo " Change Password " . PHP_EOL;        
        $this->markTestSkipped('');
        
        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );
        

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            //Show the response
            echo PHP_EOL . " Response:  " . $response = $this->_rest->put(
                $this->_url.$model."/".$action, array(
                'newpassword' => $newpassword)
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
            
        }
       
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests Change Asset Status from Inoperation to Damaged and visa versa
     * 
     * @return void
     */     
    
    public function testChangeAssetStatus()
    {
        //Request Parameters       
        $model = 'Assets';
        $id = '28x5';
           
        //Label the Test
        echo " Changing Asset Status" . PHP_EOL;        
        $this->markTestSkipped(''); 

        //login using each credentials
        foreach ($this->_credentials as $username => $password) { 
            
            // Generate signature
            list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), 
                uniqid()
            );
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            //Show the reponse
            echo PHP_EOL . " Response:  " . $response = $this->_rest->put(
                $this->_url.$model."/".$id, array('assetstatus' => 'In Service')
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->result->assetstatus, 'In Service', 
                    " Checking validity of response"
                );
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );   
            } else {
                $this->assertInstanceOf('stdClass', $response);     
            }
            
            // Generate signature
            list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), 
                uniqid()
            );
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            //Show the response
            echo PHP_EOL . " Response: " . $response = $this->_rest->put(
                $this->_url.$model."/".$id, array(
                    'assetstatus' => 'Out-of-service'
                )
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->result->assetstatus, 'Out-of-service', 
                    " Checking validity of response"
                );
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests Reseting password
     * 
     * @return void
     */ 

    public function testResetPassword()
    {
        //Request Parameters     
        $model = 'Authenticate';
        $action = 'reset';
        $method = 'PUT';      

        //Label the test
        echo " Resetting password " . PHP_EOL;  
        
        //Skipping Test
        $this->markTestSkipped('');         

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //Set Reset Pasword credentials
        $this->_credentials = array(
                'anshuk-kumar@essindia.co.in' => 'ik13qfek'
            );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            //Show the response
            echo PHP_EOL . " Response: " . $response = $this->_rest->put(
                $this->_url.$model."/".$action
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                //echo json_encode($response) . PHP_EOL;
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Test getting Asset from id
     * 
     * @return void
     */ 

    public function testGetAssetFromId()
    {
        //Request Parameters
        $model = 'Assets';
        $id = '0';
        $method = 'GET';

        //Label the test
        echo " Getting Asset From ID $id" . PHP_EOL;
        
        //Skip the test 
        $this->markTestSkipped('');
        
        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            echo " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/$id"
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        } 
    }

    /**
     * Tests Getting Asset List
     * 
     * @return void
     */     
    
    public function testGetAssetList()
    {
        //Request Parameters
        $model = 'Assets';
        $action = 'inoperation';
        $method = 'GET';

        //Label the test
        echo " Getting Asset List " . PHP_EOL;        

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c", time()+70), 
            uniqid()
        );
            
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
          
            echo PHP_EOL . "URL: " . $this->_url.$model;
            //Show the response
            echo PHP_EOL . " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/".$action
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        } 
    }
 
    /**
     * Tests getting Trouble Ticket from Inoperation List
     * 
     * @return void
     */ 
    
    public function testGetTroubleTicketInoperationList()
    {   
        //Request Parameters
        $model = 'HelpDesk';
        $category = 'inoperation';
        $method = 'GET';

        //Label the test
        echo " Getting Ticket Inoperation " . PHP_EOL;        

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            //Show the response
            echo " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/$category"
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
        
        echo PHP_EOL . PHP_EOL;        
        
    }

    /**
     * Tests getting Trouble Ticket from Inoperation List with Filter
     * 
     * @return void
     */     
    
    public function testGetTroubleTicketInoperationListWithFilter()
    {
        //Request Parameter
        $model = 'HelpDesk';
        $category = 'all';
        $filter = Array(
            'year' => '0000',
            'month' => '00',
            'trailerid' => '0',
            'reportdamage' => 'all'
        );
        $method = 'GET';
        
        //Label the test
        echo " Getting Ticket Inoperation With Filter" . PHP_EOL;        

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            //Show the URL
            echo " Request URL: " . $this->_url.$model."/$category"."/".
                $filter['year']."/".
                $filter['month']."/".
                $filter['trailerid']."/".
                $filter['reportdamage'] . PHP_EOL;
            //Show the response
            echo " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/$category"."/".
                $filter['year']."/".
                $filter['month']."/".
                $filter['trailerid']."/".
                $filter['reportdamage']
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        } 
        
        echo PHP_EOL . PHP_EOL;        
        
    }
  
    /**
     * Tests getting Damaged Trouble Ticket List
     * 
     * @return void
     */     
    
    public function testGetTroubleTicketDamagedList()
    {
        //Request Parameters
        $model = 'HelpDesk';
        $category = 'damaged';
        $method = 'GET';

        //Label the test
        echo " Getting Ticket Damaged " . PHP_EOL;        

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            echo " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/$category"
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
            unset($rest);
        } 
    }

    /**
     * Tests getting Trouble Ticket from Id
     * 
     * @return void
     */     
    
    public function testGetTroubleTicketFromId()
    {
        //Request Parameters
        $model = 'HelpDesk';
        $id = '17x204';
        $method = 'GET';

        //Label the test
        echo " Getting Ticket From ID $id" . PHP_EOL;
        
        //Skip the test 
        //$this->markTestSkipped('');
        
        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            echo " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/$id"
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $this->assertEquals(
                    $response->success, true, " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        } 
    }

    /**
     * Tests getting Trouble Ticket from Inoperation List
     * 
     * @return void
     */    
    
    public function testCreateTroubleTicketWithOutDocument()
    {
        //Request Parameters
        $method = 'POST';
        $model = 'HelpDesk';

        //Label the test
        echo " Creating Trouble Ticket With Out Document" . PHP_EOL;        

        //set fields to to posted
        $fields = array(
                'ticket_title'=> 'Testing Using PHPUnit',
                'drivercauseddamage'=>'No',
                'sealed'=>'Yes',
                'plates'=>'3',
                'straps'=>'2',
                'damagetype'=> 'Aggregatkåpa',
                'damageposition' => 'Vänster sida (Left side)',
                'ticketstatus' => 'Open',      
                'reportdamage' => 'Yes',
                'trailerid'=>'ASVVSD001'              
            );

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            //Show the response
            echo " Response: " . $response = $this->_rest->post(
                $this->_url.$model, $fields
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                $message = '';
                if (isset($response->error->message)) 
                    $message = $response->error->message;

                $this->assertEquals($response->success, true, $message);
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        } 
    }

    /**
     * Tests getting Trouble Ticket With Document
     * 
     * @return void
     */     
    
    public function testCreateTroubleTicketWithDocument()
    {
        //Request Parameters 
        $model = 'HelpDesk';
        $method = 'POST';
        
        //Setting infinite execution time for this script
        set_time_limit(0); 
        
        //Label the Test
        echo " Creating Trouble Ticket with Document " . PHP_EOL;        

        //Set fields to to posted
        $fields = array(
                'ticket_title'=>'Testing Using PHPUnit with Image Upload',
                'filename'=>'@'.getcwd().'/images/image-to-upload.png',
                //'filename-1'=>'@'.getcwd().'/images/image-to-upload-1.png',
                //'filename-2'=>'@'.getcwd().'/images/image-to-upload-2.png',
                //'filename-3'=>'@'.getcwd().'/images/image-to-upload-3.png',
                //'filename-4'=>'@'.getcwd().'/images/image-to-upload-4.png',
                //'filename-5'=>'@'.getcwd().'/images/image-to-upload-5.png',
                'ticket_title'=> 'Testing Using PHPUnit',
                'drivercauseddamage'=>'No',
                'sealed'=>'Yes',
                'plates'=>'3',
                'straps'=>'2',
                'damagetype'=> 'Aggregatkåpa',
                'damageposition' => 'Vänster sida (Left side)',
                'ticketstatus' => 'Open',      
                'reportdamage' => 'Yes',
                'trailerid'=>'ASVVSD001'              
        );

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
        
            //Set Header
            $this->_setHeader($username, $password, $params, $signature);
        
            echo PHP_EOL . " Response: " . $response = $this->_rest->post(
                $this->_url.$model, $fields
            );
            $response = json_decode($response);
            
            //check if response is valid
            if (isset($response->success)) {
                echo " Generated Ticket ID " . $response->result->id . PHP_EOL;
                $message = '';
                if (isset($response->error->message)) 
                    $message = $response->error->message;

                $this->assertEquals($response->success, true, $message);
                $this->assertNotEmpty($response->result->documents);
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
            unset($rest);
        }  
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests Generating Signature Hash
     * 
     * @return void
     */     
    
    public function testSignatureHash() 
    {
        
        //Label the test
        echo " Matching Signature Hash " . PHP_EOL;
        
        //Skip the Test
        //$this->markTestSkipped('');        

        // Generate signature
        /*
        list($params, $signature) = $this->_generateSignature(
            $method, $model, date("c"), 
            uniqid()
        );
        */
        list($params, $signature) = $this->_generateSignature(
            'POST', 'Authenticate', '20130117T10:21:24+0530', 
            '1980550879'
        );
        print_r($params);
        $signatureGenerated = 'gjkXqfwN67RCI+bTz+Riq6LSTeCpJ5AKd4YBIYrf2Mw=';
        $this->assertEquals($signature, $signatureGenerated);
    }

    /**
     * Tests Uploading files to Amazon S3
     * 
     * @return void
     */     
    
    public function testUploadToAmazonS3()
    {
        echo " Uploading File To Amazons3" . PHP_EOL;
        $this->markTestSkipped('');
                        $sThree = new AmazonS3();
                        
                        $file = Array(
                            'name' => getcwd().'/images/image-to-upload.jpg'
                        );

                        $response = $sThree->create_object(
                            'gizurcloud-gc', 
                            'image-to-upload.jpg', 
                            array(
                                //'acl' => AmazonS3::ACL_PUBLIC,
                                'fileUpload' => $file['name'],
                                'contentType' => 'image/jpeg',
                                //'storage' => AmazonS3::STORAGE_REDUCED,
                                'headers' => array(
                                    'Cache-Control'    => 'max-age',
                                    //'Content-Encoding' => 'gzip',
                                    'Content-Language' => 'en-US',
                                    'Expires'          => 
                                    'Thu, 01 Dec 1994 16:00:00 GMT',
                                )   
                            )
                        );                        
                        $this->assertEquals($response->isOK(), true);
    }

    /**
     * Tests Getting document attachement
     * 
     * @return void
     */       
    
    public function testGetDocumentAttachment()
    {
        //Request Parameters
        $model = 'DocumentAttachments';
        $notesid = '15x13';
        $method = 'GET';

        //Label the test
        echo " Downloading Ticket Attachement " . PHP_EOL;   
        
        //Skip the test
        $this->markTestSkipped('');         
    
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {     
            
            // Generate signature
            list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), 
                uniqid()
            );

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            echo " Response: " . $response = $this->_rest->get(
                $this->_url.$model."/".$notesid
            );
            $response = json_decode($response);

            //check if response is valid
            if (isset($response->success)) {
                $message = '';
                if (isset($response->error->message)) 
                    $message = $response->error->message;

                $this->assertEquals($response->success, true, $message);
                $this->assertNotEmpty($response->result->filecontent);
                $fp = fopen('downloaded_'.$response->result->filename, 'w');
                fwrite($fp, base64_decode($response->result->filecontent));
                fclose($fp);
                $this->assertFileEquals(
                    'downloaded_'.$response->result->filename, 
                    $response->result->filename
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
    }

    /**
     * Tests Getting Picklists
     * 
     * @return void
     */       
    
    public function testGetPicklist()
    {
        //Request Parameters
        $method = 'GET';
        $model = 'HelpDesk';
        
        //$fieldname[0] = 'ticketstatus';
        $fieldnames = array(
            'ticketpriorities', 
            'ticketseverities', 
            'ticketstatus',
            'ticketcategories',
            'tickettype',
            'sealed',
            'reportdamage',
            'damagetype',
            'damageposition',
            'drivercauseddamage',
            'damagereportlocation'
        );

        //Label the test
        echo " Getting Picklist" . PHP_EOL;        

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
            
            //Loop throug all fieldnames and access them
            foreach ($fieldnames as $fieldname) {
                
                //Reset REST Handle
                $this->setUp();
                
                // Generate signature
                list($params, $signature) = $this->_generateSignature(
                    $method, $model, date("c"), 
                    uniqid()
                );

                //Set Header
                $this->_setHeader($username, $password, $params, $signature);

                //Show the response
                echo PHP_EOL . " Response ($fieldname): " .$response = 
                    $this->_rest->get(
                        $this->_url.$model."/".$fieldname
                    );

                $response = json_decode($response);
                
                //check if response is valid
                if (isset($response->success)) {
                    $message = '';
                    if (isset($response->error->message)) 
                        $message = $response->error->message;
                    $this->assertEquals($response->success, true, $message);
                } else {
                    $this->assertInstanceOf('stdClass', $response);
                }
            }   
        }
        
        echo PHP_EOL . PHP_EOL;        
    }  
    
    /**
     * Tests Getting picklist for assets
     * 
     * @return void
     */       

    public function testGetPicklistAssets()
    {
        //Request Parameters
        $method = 'GET';
        $model = 'Assets';
        
        //$fieldname[0] = 'ticketstatus';
        $fieldnames = array(
            'trailertype'
        );

        //Label the test
        echo " Getting Picklist for Assets" . PHP_EOL;        

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {            
            
            //Loop throug all fieldnames and access them
            foreach ($fieldnames as $fieldname) {
                
                //Reset REST Handle
                $this->setUp();
                
                // Generate signature
                list($params, $signature) = $this->_generateSignature(
                    $method, $model, date("c"), 
                    uniqid()
                );

                //Set Header
                $this->_setHeader($username, $password, $params, $signature);

                //Show the response
                echo PHP_EOL . " Response: " .$response = $this->_rest->get(
                    $this->_url.$model."/".$fieldname
                );

                $response = json_decode($response);
                
                //check if response is valid
                if (isset($response->success)) {
                    $message = '';
                    if (isset($response->error->message)) 
                        $message = $response->error->message;
                    $this->assertEquals($response->success, true, $message);
                } else {
                    $this->assertInstanceOf('stdClass', $response);
                }
            }   
        }
        
        echo PHP_EOL . PHP_EOL;        
    }

    /**
     * Tests Get User Details
     * 
     * @return void
     */       
    
    public function testGetUserDetails()
    {
        //Request Parameters
        $method = 'GET';
        $model = 'User';
        $userEmail = 'cloud3%40gizur.com';
        
        //Label the test
        echo " Getting Details of User" . PHP_EOL;        

        echo $this->_url.$model."/".$userEmail;

        $this->_rest->set_header('X_UNIQUE_SALT', uniqid());        

        //Show the response
        echo PHP_EOL . " Response: " .$response = $this->_rest->get(
            $this->_url.$model."/".$userEmail
        );

        $response = json_decode($response);
        
        //check if response is valid
        if (isset($response->success)) {
            $message = '';
            if (isset($response->error->message)) 
                $message = $response->error->message;
            $this->assertEquals($response->success, true, $message);
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }
        
        echo PHP_EOL . PHP_EOL;        
    } 

    /**
     * Tests Get User Details
     * 
     * @return void
     */       
    
    public function testCreateUser()
    {
        //Request Parameters
        $method = 'POST';
        $model = 'User';
        $uniqueName = substr(strrev(uniqid()), 1, 8);
        $userEmail = "cloud_{$uniqueName}%40gizur.com";
        
        //Label the test
        echo " Creating New User $userEmail" . PHP_EOL;        

        echo $this->_url.$model."/".$userEmail;

        $this->_rest->set_header('X_UNIQUE_SALT', uniqid());        

        //Show the response
        echo PHP_EOL . " Response: " .$response = $this->_rest->post(
            $this->_url.$model, json_encode(
                array(
                'id' => $userEmail
                )
            )
        );

        $response = json_decode($response);
        
        //check if response is valid
        if (isset($response->success)) {
            $message = '';
            if (isset($response->error->message)) 
                $message = $response->error->message;

            $this->assertEquals($response->success, true, $message);
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }
        
        echo PHP_EOL . PHP_EOL;        
    }     
 
}
