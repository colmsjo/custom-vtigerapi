<?php

/**
 * PHP Unit Test cases class. Testing Gizur API Login
 * without API keys
 * 
 * PHP version 5
 * 
 * @category   Test
 * @package    Gizur
 * @subpackage Login
 * @author     Anshuk Kumar <anshuk-kumar@essindia.co.in>
 * @copyright  2012 &copy; Gizur AB
 * @license    Gizur Private Licence
 * @link       http://www.gizur.com
 */
/**
 * Unit Test class for Testing the Gizur REST API ( wrapper over 
 * vtiger REST API )
 * Contains methods which test  
 * Login / authentication
 * 
 * Testing method:
 * > phpunit Login/
 */
//echo __DIR__;
//die('kiya');
require_once realpath(__DIR__ . '/config.inc.php');
require_once 'PHPUnit/Autoload.php';
require_once realpath(__DIR__ . '/../lib/RESTClient.php');
require_once realpath(__DIR__ . '/../../lib/aws-php-sdk/sdk.class.php');

/**
 * Gizur Test inherits PHP Unit Tests Framework
 * 
 * @category  Test
 * @package   Gizur 
 * @author    Anshuk Kumar <anshuk-kumar@essindia.co.in>
 * @copyright 2012 &copy; Gizur AB
 * @license   Gizur Private Licence
 * 
 * @link      http://www.gizur.com
 */
class UserSpecificTests extends PHPUnit_Framework_TestCase
{
    private $_rest;
    private $_gizurCloudApiKey    = 'AKIAJX43RR2UCVINIL3Q';
    private $_gizurcloudSecretKey = '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';
    private $_apiVersion          ='0.1';
    private $_clientid;
    private $_credentials =Array();
    private $_url         = <<<URL
http://phpapplications-env-sixmtjkbzs.elasticbeanstalk.com/api/
URL;

    /** setting header values 
     * 
     * @param string $username username   
     * @param string $password password  
     * @param string $clientid client id
     * 
     * @return void
     * **/
    private function _setHeader($username, $password, $clientid)
    {

        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        $this->_rest->set_header('X_CLIENTID', $clientid);
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
        $this->_rest->language(array('en-us;q=0.5', 'sv'));
        $config             = new Configuration();
        $configuration      = $config->get();
        $this->_url         = $configuration['url'];
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
     * test case of background process details when image uploads 
     *
     *  @return void
     */    
    public function testUserdetailsByEmail() 
    {

        $model  = 'User';
        $action = 'email';
        $method = 'GET';
        $delta  = 0;
        
        // echo 'gizur key'.$this->_gizurCloudApiKey;
        echo " User details after log in" . PHP_EOL;
        $this->setUp();
        $password = $this->_credentials['password'];
        $username = $this->_credentials['id'];
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        
        //Show the response
        echo " Response: " . $response = $this->_rest->get($this->_url .
                $model . "/" . $username);
        $response                      = json_decode($response);
        //check if response is valid
        
        if (isset($response->success)) {
            
            $this->assertEquals($response->success, true,
                    " Checking validity of response");
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }
    }
    /**
    * test case to update user information
     * 
     * @return void
     */
    
    public function testUpdateDetails()
    {
       
        $model  = 'User';
        $action = 'email';
        $method = 'GET';
        $delta  = 0;
         
        // echo 'gizur key'.$this->_gizurCloudApiKey;
        echo " User details after log in" . PHP_EOL;
        $this->setUp();
        $password = $this->_credentials['password'];
        $username = $this->_credentials['id'];
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        
        //Show the response
        echo " Response: " . $response = $this->_rest->get($this->_url . $model
                . "/" . $username);
        $response                      = json_decode($response, true);
        //check if response is valid
        if (isset($response->success)) {
            
            $this->assertEquals($response->success, true, 
                    " Checking validity of response");
        
            $response['result']['name_1'] = 'rajusen';
            $delta                        = 0;
            $action                       = '';
            $model                        = 'User';
            $method                       = 'PUT';

            $this->setUp();
            $password = $this->_credentials['password'];
            $username = $this->_credentials['id'];


            $this->_rest->set_header('Accept', 'application/json');
            $this->_rest->set_header('Content-Type', 'application/json');


            $this->_rest->set_header('X_USERNAME', $username);
            $this->_rest->set_header('X_PASSWORD', $password);
            //Show the response
            echo " Response: " . $response = $this->_rest->put($this->_url . 
                    $model, json_encode($response['result']));
            $response                      = json_decode($response);
            //print_r($response);
            if (isset($response->success)) {
                    $this->assertEquals($response->success, true, 
                            " Checking validity of response");
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        } else {
                $this->assertInstanceOf('stdClass', $response);
        }
        
        
    }
    /**
    test api secret key and api key
     * 
     * @return void
     */
    
    public function testApikeyDetails()
    {
     
        $model  = 'User';
        $action = 'email';
        $method = 'GET';
        $delta  = 0;
        
        // echo 'gizur key'.$this->_gizurCloudApiKey;
        echo " User details after log in" . PHP_EOL;
        $this->setUp();
        $password = $this->_credentials['password'];
        $username = $this->_credentials['id'];
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        
        //Show the response
        echo " Response: " . $response = $this->_rest->get($this->_url .
                $model . "/" . $username);
        $response                      = json_decode($response);
        //check if response is valid
        $_GET['field'] = 'keypair2';
        $keyid         = str_replace('keypair', '', $_GET['field']);

       
        $delta  = 0;
        $action = '';
        $model  = 'User';
        $method = 'PUT';

        $this->setUp();
        $password = $this->_credentials['password'];
        $username = $this->_credentials['id'];
        
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('Content-Type', 'application/json');
        
       
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        //Show the response
        echo " Response: " . $response = $this->_rest->put($this->_url . $model
                .'/'.$_GET['field'].'/'.$username,
                json_encode($response['result']));
        $response                      = json_decode($response);
        
        if (isset($response->success)) {
            $this->assertEquals($response->success, true,
                  " Checking validity of response");
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }
        
    }
    /**
      test case to create copyuser
     * 
     * @return void
     */
    
    public function testCopyUserDetails() 
    {

        $model  = 'User';
        $action = 'email';
        $method = 'GET';
        $delta  = 0;
        
        // echo 'gizur key'.$this->_gizurCloudApiKey;
        echo " User details after log in" . PHP_EOL;
        $this->setUp();
        $password = $this->_credentials['password'];
        $username = $this->_credentials['id'];
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        
        //Show the response
        echo " Response: " . $response = $this->_rest->get($this->_url . $model 
                . "/" . $username);
        $response                      = json_decode($response, true);

        $response['result']['databasename'];
        $response['result']['server'];
        $response['result']['port'];
        $response['result']['dbpassword'];
        $response['result']['username'] = "vidya.bhushan1";
        $response['result']['clientid'] = "vidya1";
        $response['result']['id']       = "vidya.bhushan1@essindia.co.in";
        $response['result']['password'] = $response['result']['dbpassword'];

        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('Content-Type', 'application/json');
        
       
        $this->_rest->set_header('X_USERNAME', $username);
        $this->_rest->set_header('X_PASSWORD', $password);
        //Show the response
  
        $model                         = 'User';
        $action                        = 'copyuser';
        echo " Response: " . $response = $this->_rest->post($this->_url . 
                $model.'/'.$action, json_encode($response['result']));
        $response                      = json_decode($response);
        

        if (isset($response->success)) {
            $this->assertEquals($response->success, true, 
                  " Checking validity of response");
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }         
        
    }
    /**
    test case to create create new user or registration process
     * 
     * @return void
     */    
    
    public function testCreateUserDetails() 
    {

        $delta = 0;
        
        $this->setUp();
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_rest->set_header('Content-Type', 'application/json');
        $response['result']             = array();
        $response['result']['clientid'] = "ram1";
        $response['result']['id']       = "ram.singh@essindia.co.in";
        $response['result']['password'] = '123456';
        $response['result']['name_1']   = 'ram';
        $response['result']['name_2']   = 'singh';

        //Show the response
        $model                         = 'User';
        $action                        = '';
        echo " Response: " . $response = $this->_rest->post($this->_url . 
                $model, json_encode($response['result']));
        $response                      = json_decode($response);
         

        if (isset($response->success)) {
            $this->assertEquals($response->success, true,
                  " Checking validity of response");
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }         
        
    }
    /**
    test case of background process details when image uploads 
    * 
     * @return void
     */ 
    
    public function testImageBackgroundDetails() 
    {

        $model  = 'Background';
        $action = 'backgroundstatus';
        $method = 'GET';
        $delta  = 0;
        
        // echo 'gizur key'.$this->_gizurCloudApiKey;
        echo " Background process while image upload " . PHP_EOL;
        $this->setUp();
        $password = $this->_credentials['password'];
        $username = $this->_credentials['id'];
        $clientid = $this->_credentials['clientid'];
        
        $this->_rest->set_header('Accept', 'application/json');
        $this->_setHeader($username, $password, $clientid);
         //Show the response
        echo " Response: " . $response = $this->_rest->get($this->_url . $model
                . "/" . $action);
        $response                      = json_decode($response);
        //check if response is valid
        
        if (isset($response->success)) {
            $this->assertEquals($response->success, true, 
                    " Checking validity of response");
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }
    }
    /**
    test case of login functionality 
     * 
     * @return void
     */ 
    
    public function testLogin() 
    {

        $model  = 'User';
        $action = 'login';
        $method = 'POST';
        $data   = json_encode($this->_credentials);

        //Create REST handle
        $this->setUp();
        $this->_rest->set_header('Accept', 'application/json');

        echo PHP_EOL . " Response: " .
                $response = $this->_rest->post($this->_url.$model."/".$action, 
                        $data);

        $response = json_decode($response);

        //check if response is valid
        if (isset($response->success)) {

            $this->assertEquals($response->success, true,
                    " Checking validity of response");

        } else {

            $this->assertInstanceOf('stdClass', $response);
        }


    }
    /**
    * Tests for ForgotPassword
     * 
     * @return void
     */ 
    
    public function testForgot()
    {

        $model  = 'User';
        $action = 'forgotpassword';
        $method = 'POST';

        $data = json_encode($this->_credentials);

        //Create REST handle
        $this->setUp();
        $this->_rest->set_header('Accept', 'application/json');

        echo PHP_EOL . " Response: " .
        $response = $this->_rest->post($this->_url.$model.
                "/".$action, $data);

        $response = json_decode($response);

        //check if response is valid
        if (isset($response->success)) {

            $this->assertEquals($response->success, true,
                    " Checking validity of response");
        } else {
            $this->assertInstanceOf('stdClass', $response);
        }

    }



}
