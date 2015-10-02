<?php

require_once realpath(__DIR__ . '/config.inc.php');
require_once realpath(__DIR__ . '/lib/RESTClient.php');

class Custom_API_Test extends PHPUnit_Framework_TestCase {

    private $_gizurCloudSecretKey = "";
    private $_gizurCloudApiKey = "";
    private $_apiVersion = "0.1";
    private $_rest;
    private $_credentials = Array();
    private $_url = <<<URL
"http://localhost/api/"
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
    private function _generateSignature($method, $model, $timestamp, $uniqueSalt) {
        //Build array
        $params = array(
            'Verb' => $method,
            'Model' => $model,
            'Version' => $this->_apiVersion,
            'Timestamp' => $timestamp,
            'KeyID' => $this->_gizurCloudApiKey,
            'UniqueSalt' => $uniqueSalt
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
    private function _setHeader($username, $password, $params, $signature) {
   //     print_r($_SERVER);die;
        $this->_rest->set_header('X-USERNAME', $username);
        $this->_rest->set_header('X-PASSWORD', $password);
        $this->_rest->set_header('X-TIMESTAMP', $params['Timestamp']);
        $this->_rest->set_header('X-SIGNATURE', $signature);
        $this->_rest->set_header('X-CLIENTID', 'clab');
        $this->_rest->set_header('ACCEPT-LANGUAGE', 'en');
        $this->_rest->set_header(
                'X-GIZURCLOUD_API-KEY', $this->_gizurCloudApiKey
        );
        $this->_rest->set_header('X-UNIQUE-SALT', $params['UniqueSalt']);
       if($_SERVER['HOSTNAME'] == 'lampClab1'){	 $addr='192.0.0.0';
     }else if($_SERVER['HOSTNAME'] == 'lampClab2'){
$addr='192.0.0.1';
}   $this->_rest->set_header('X-SERVERADDR', $addr);

    }

    /**
     * Executed before every Test case
     * 
     * @return void
     */
    protected function setUp() {
        $this->_rest = new RESTClient();
        $this->_rest->format('json');
        $this->_rest->ssl(false);
        $this->_rest->language(array('en-us;q=0.5', 'sv'));
        $config = new Configuration();
        $configuration = $config->get();

        $this->_url = $configuration['url'];
        $this->_gizurCloudApiKey = $configuration['GIZURCLOUD_API_KEY'];
        $this->_gizurcloudSecretKey = $configuration['GIZURCLOUD_SECRET_KEY'];
        $this->_apiVersion = $configuration['API_VERSION'];
        $this->_credentials = $configuration['credentials'];
    }

    public function testLoginSingle() {
        $model = 'Authenticate';
        $action = 'login';
        $method = 'POST';
        $delta = 0;


        echo " Authenticating Login " . PHP_EOL;

        //set credentials
        $this->_credentials = Array(
            'niclas.andersson@coop.se' => 'nicand',
            'portal_user@gizur.com' => 'portal',
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
            'demo@gizur.com' => true,
            'niclas.andersson@coop.se' => true,
            'portal_user@gizur.com' => false,
        );

        Restart:

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Create REST handle
            $this->setUp();

            // Generate signature
            list($params, $signature) = $this->_generateSignature(
                    $method, $model, date("c"), uniqid()
            );

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            echo PHP_EOL . " Response: " . $response = $this->_rest->post(
            $this->_url . $model . "/" . $action
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
                        $response->success, $validCredentials[$username], " Checking validity of response"
                );
            } else {
                $this->assertInstanceOf('stdClass', $response);
            }
        }
        echo PHP_EOL . PHP_EOL;
    }

    /**
     * Tests the Change password
     * 
     * @return void
     */
    public function testChangePassword() {

        //Request parameters
        $model = 'Authenticate';
        $action = 'changepw';
        $method = 'PUT';
        $newpassword = '123456';
        //$newpassword = 'ipjibl0f';


        $this->_credentials = Array(
            'niclas.andersson@coop.se' => 'nicand',
        );

        //Label the Test
        echo " Change Password " . PHP_EOL;
        $this->markTestSkipped('');

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            //Show the response
            echo PHP_EOL . " Response:  " . $response = $this->_rest->put(
            $this->_url . $model . "/" . $action, array(
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
     * Test getting Asset from id
     * 
     * @return void
     */
    public function testGetAssetFromId() {
        //Request Parameters
        $model = 'Assets';
        $id = '28x427';
        $method = 'GET';

        //Label the test
        echo " Getting Asset From ID $id" . PHP_EOL;

        //Skip the test 
        //$this->markTestSkipped('');
        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            echo " Response: " . $response = $this->_rest->get(
            $this->_url . $model . "/$id"
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
    public function testGetAssetList() {
        //Request Parameters
        $model = 'Assets';
        $action = 'inoperation';
        $method = 'GET';

        //Label the test
        echo " Getting Asset List " . PHP_EOL;

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c", time() + 70), uniqid()
        );
        //Skip the test 
        //$this->markTestSkipped('');
        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            echo PHP_EOL . "URL: " . $this->_url . $model;
            //Show the response
            echo PHP_EOL . " Response: " . $response = $this->_rest->get(
            $this->_url . $model . "/" . $action
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
    public function testGetTroubleTicketInoperationList() {
        //Request Parameters
        $model = 'HelpDesk';
        $category = 'inoperation';
        $method = 'GET';

        //Skip the test 
        $this->markTestSkipped('');

        //Label the test
        echo " Getting Ticket Inoperation " . PHP_EOL;

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            //Show the response
            echo " Response: " . $response = $this->_rest->get(
            $this->_url . $model . "/$category"
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
    public function testGetTroubleTicketInoperationListWithFilter() {
        //Request Parameter
        $model = 'HelpDesk';
        $category = 'all';
        $filter = Array(
            'year' => '0000',
            'month' => '00',
            'trailerid' => '0',
            'reportdamage' => 'all',
            'ticketstatus' => 'open'
        );
        $method = 'GET';
        $min_limit = 0;
        $max_limit = 10;

        //Label the test
        echo " Getting Ticket Inoperation With Filter" . PHP_EOL;

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            //Show the URL
            echo " Request URL: " . $this->_url . $model . "/" . $min_limit . "/" . $max_limit . "/$category" . "/" .
            $filter['year'] . "/" .
            $filter['month'] . "/" .
            $filter['trailerid'] . "/" .
            $filter['reportdamage'] . "/" .
            $filter['ticketstatus'] . PHP_EOL;
            //Show the response
            echo " Response: " . $response = $this->_rest->get(
            $this->_url . $model . "/" . $min_limit . "/" . $max_limit . "/$category" . "/" .
            $filter['year'] . "/" .
            $filter['month'] . "/" .
            $filter['trailerid'] . "/" .
            $filter['reportdamage'] . "/" .
            $filter['ticketstatus']
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
    public function testGetTroubleTicketDamagedList() {
        //Request Parameters
        $model = 'HelpDesk';
        $category = 'damaged';
        $method = 'GET';

        //Label the test
        echo " Getting Ticket Damaged " . PHP_EOL;

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            echo " Response: " . $response = $this->_rest->get(
            $this->_url . $model . "/$category"
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
    public function testGetTroubleTicketFromId() {
        //Request Parameters
        $model = 'HelpDesk';
        $id = '17x15525';
        $method = 'GET';

        //Label the test
        echo " Getting Ticket From ID $id" . PHP_EOL;

        //Skip the test 
        //$this->markTestSkipped('');
        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            echo " Response: " . $response = $this->_rest->get(
            $this->_url . $model . "/$id"
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

    public function testGetPicklist() {
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
                        $method, $model, date("c"), uniqid()
                );

                //Set Header
                $this->_setHeader($username, $password, $params, $signature);

                //Show the response
                echo PHP_EOL . " Response ($fieldname): " . $response = $this->_rest->get(
                $this->_url . $model . "/" . $fieldname
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
    public function testGetPicklistAssets() {
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
                        $method, $model, date("c"), uniqid()
                );

                //Set Header
                $this->_setHeader($username, $password, $params, $signature);

                //Show the response
                echo PHP_EOL . " Response: " . $response = $this->_rest->get(
                $this->_url . $model . "/" . $fieldname
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

    public function testCreateTroubleTicketWithOutDocument() {
        //Request Parameters
        $method = 'POST';
        $model = 'HelpDesk';

         //Skip the test 
        //$this->markTestSkipped('');
        //Label the test
        echo " Creating Trouble Ticket With Out Document" . PHP_EOL;

        //set fields to to posted
        $fields = array(
            'ticket_title' => 'Testing Using PHPUnit',
            'drivercauseddamage' => 'No',
            'sealed' => 'Yes',
            'plates' => '3',
            'straps' => '2',
            'damagereportlocation'=>'',
            'damagetype' => 'Aggregatkåpa',
            'damageposition' => 'Vänster sida (Left side)',
            'ticketstatus' => 'Open',
            'reportdamage' => 'Yes',
            'trailerid' => 'XXXTEST'
        );

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            //Show the response
            
            echo " Response: " . $response = $this->_rest->post(
            $this->_url . $model, $fields
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

  /*
public function testCreateTroubleTicketWithDocument() {
        //Request Parameters
        $method = 'POST';
        $model = 'HelpDesk';

         //Skip the test 
        //$this->markTestSkipped('');
        //Label the test
        echo " Creating Trouble Ticket with Document" . PHP_EOL;

        //set fields to to posted
        $fields = array(
            'ticket_title'=>'Testing Using PHPUnit with Image Upload',
                'filename'=>'@'.getcwd().'/images/image-to-upload.png',
                'ticket_title'=> 'Testing Using PHPUnit',
                'drivercauseddamage'=>'No',
                'sealed'=>'Yes',
                'plates'=>'3',
                'straps'=>'2',
                'damagetype'=> 'Aggregatkåpa',
                'damageposition' => 'Vänster sida (Left side)',
                'ticketstatus' => 'Open',      
                'reportdamage' => 'Yes',
                'trailerid'=>'XXXTEST'         
        );

        // Generate signature
        list($params, $signature) = $this->_generateSignature(
                $method, $model, date("c"), uniqid()
        );

        //login using each credentials
        foreach ($this->_credentials as $username => $password) {

            //Set Header
            $this->_setHeader($username, $password, $params, $signature);

            //Show the response
            
            echo " Response: " . $response = $this->_rest->post(
            $this->_url . $model, $fields
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
    } */

}
