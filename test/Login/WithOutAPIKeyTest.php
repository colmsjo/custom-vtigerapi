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
 * @version    0.2
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

require_once realpath(__DIR__ . '/../config.inc.php');
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
 * @version   0.2
 * @link      http://www.gizur.com
 */

class WithOutAPIKeyTest extends PHPUnit_Framework_TestCase
{
    private $_rest;
    
    private $_clientid;

    private $_credentials = Array();

    private $_url = <<<URL
http://phpapplications-env-sixmtjkbzs.elasticbeanstalk.com/api/
URL;

    /**
     * Sets the header from for CURL
     * 
     * @param string $username  string to be set to HTTP_X_USERNAME header
     * @param string $password  string to be set to HTTP_X_PASSWORD header
     * 
     * @return null
     */    
    
    private function _setHeader($username, $password, $clientid)
    {
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
        $this->_rest->language(array('en-us;q=0.5','sv'));        
        $config = new Configuration();
        $configuration = $config->get();
        $this->_clientid = $configuration['clientid'];
        $this->_url = $configuration['url'];
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
        
        
        echo " Authenticating Login without API Keys" . PHP_EOL;        
  
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

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {  

            //Create REST handle
            $this->setUp();            

            //Set Header

            $this->_setHeader($username, $password, $this->_clientid);  
            
            echo PHP_EOL . " Response: " . $response = $this->_rest->post(
                $this->_url.$model."/".$action
            );
            
            $response = json_decode($response);

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
}
