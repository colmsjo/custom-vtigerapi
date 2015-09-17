<?php

/**
 * Yii Controller to handle REST queries
 *
 * Works with remote vtiger REST service
 * 
 * PHP version 5
 *
 * @category   Controller
 * @package    GizurCloud
 * @subpackage Controller
 * @author     Anshuk Kumar <anshuk.kumar@essindia.co.in>
 * 
 * @license    Gizur Private Licence
 * @link       http://api.gizur.com/api/index.php
 * 
 * */
spl_autoload_unregister(array('YiiBase', 'autoload'));
Yii::import('application.vendor.*');
require_once 'aws-php-sdk/sdk.class.php';
spl_autoload_register(array('YiiBase', 'autoload'));

class ApiController extends Controller {

    /**
     * Trace ID
     */
    private $_traceId = "";
    private $_vtresponse = "";
    private $_vtresturl = "";
    private $_clientid = "";
    private $_cacheKey = "";

    /**
     * Error Codes
     */
    private $_errors = Array(
        0 => "ERROR",
        1001 => "MANDATORY_FIELDS_MISSING",
        1002 => "INVALID_FIELD_VALUE",
        1003 => "TIME_NOT_IN_SYNC",
        1004 => "METHOD_NOT_ALLOWED",
        1005 => "MIME_TYPE_NOT_SUPPORTED",
        1006 => "INVALID_SESSIONID",
        2001 => "CLIENT_ID_INVALID",
        2002 => "EMAIL_INVALID",
        2003 => "LOGIN_INVALID",
        2004 => "WRONG_CREDENTIALS",
        2005 => "WRONG_FROM_CLIENT",
        2006 => 'INVALID_EMAIL'
    );

    /**
     * Status Codes
     */
    private $_codes = Array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
    );

    protected function beforeAction($action) {
//echo"<pre>";       die(print_r($_SERVER));
	try {
            $this->_traceId = uniqid();
            $req = new ValidateRequest;
            $req->authenticate();
            $this->_clientid = $_SERVER['HTTP_X_CLIENTID'];
            $this->_vtresturl = str_replace(
                    '{clientid}', $this->_clientid, Yii::app()->params->vtRestUrl
            );
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
            return false;
        }
        return true;
    }

	public function actionDeleteAssets() {
       
		  try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $assets = new Assets;
             //print_r( $_GET);die;   
            $response = $assets->delete($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
	
            ob_start();
            $this->_sendResponse(200, json_encode($response));
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
	}
    public function actionDocument() {
     
                $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            $userid='19x'.$cacheresponse->result->vtigerUserId;
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
 
                    ob_start();

                    $response = new stdClass();
                    $response->success = true;
                    $response->message = "Request received.";

                    echo json_encode($response);

                    // get the size of the output
                    $size = ob_get_length();

                    // send headers to tell the browser to close the connection
                    header("Content-Length: $size");
                    header('Connection: close');

                    // flush all output
                    ob_end_flush();
                    ob_flush();
                    flush();

                    // close current session
                    if (session_id())
                        session_write_close();

                    // Loop through all Files
                    // Attach file to trouble ticket
                    $crmid = $_GET['id'];
                    $ticket_no = $_POST['ticket_no'];

                    $globalresponse->result->documents = Array();
                    $globalresponse->result->message = Array();

                    $dataJson = array(
                        'notes_title' => 'Attachement',
                        'assigned_user_id' => $userid,
                        'notecontent' => 'Attachement',
                        'filelocationtype' => 'I',
                        'filedownloadcount' => null,
                        'filestatus' => 1,
                        'fileversion' => ''
                    );

                    $globalresponse = new stdClass();

                    foreach ($_FILES as $key => $file) {

                        $uniqueid = uniqid();

                        $dataJson['filename'] = $crmid . "_" . $uniqueid .
                                "_" . $file['name'];
                        $dataJson['filesize'] = $file['size'];
                        $dataJson['filetype'] = $file['type'];

                        
                        // Upload file to Amazon S3
                        $sThree = new AmazonS3();
                        $sThree->set_region(
                                constant("AmazonS3::" . Yii::app()->params->awsS3Region)
                        );

                        $response = $sThree->create_object(
                                Yii::app()->params->awsS3Bucket, $crmid . '_' . $uniqueid . '_' . $file['name'], array(
                            'fileUpload' => $file['tmp_name'],
                            'contentType' => $file['type'],
                            'headers' => array(
                                'Cache-Control' => 'max-age',
                                'Content-Language' => 'en-US',
                                'Expires' =>
                                'Thu, 01 Dec 1994 16:00:00 GMT',
                            )
                                )
                        );

                        if ($response->isOK()) {

                            

                            //Create document
                            $rest = new RESTClient();

                            $rest->format('json');
                            $document = $rest->post(
                                    $this->_vtresturl, array(
                                'sessionName' => $cacheresponse->sessionName,
                                'operation' => 'create',
                                'element' =>
                                json_encode($dataJson),
                                'elementType' => 'Documents'
                                    )
                            );

                            $document = json_decode($document);
                            if ($document->success) {
                                $notesid = $document->result->id;

                              

                                //Relate Document with Trouble Ticket
                                $rest = new RESTClient();

                                $rest->format('json');
                                $response = $rest->post(
                                        $this->_vtresturl, array(
                                    'sessionName' => $cacheresponse->sessionName,
                                    'operation' =>
                                    'relatetroubleticketdocument',
                                    'crmid' => $crmid,
                                    'notesid' => $notesid
                                        )
                                );

                              
                                $response = json_decode($response);
                                if ($response->success) {
                                    $globalresponse->result->documents[] = $document->result;
                                    $globalresponse->result->message[] = 'File ' .
                                            ' (' . $file['name'] . ') updated.';
                                } else {
                                    $globalresponse->result->message[] = 'not' .
                                            ' uploaded - relating ' .
                                            'document failed:' . $file['name'];
                                }
                            } else {
                                $globalresponse->result->message[] = 'not uploaded' .
                                        ' - creating document failed:' . $file['name'];
                            }
                        } else {
                            $globalresponse->result->message[] = 'not uploaded - ' .
                                    'upload to storage service failed:' . $file['name'];
                        }
                    }

                    $globalresponse = json_encode($globalresponse);
                    $globalresponse = json_decode($globalresponse, true);

                   

    }

   
      public function actionCreateTroubleticket(){

                   $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
              $userid='19x'.$cacheresponse->result->vtigerUserId;
        
          if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
                     if (!isset($_POST['ticketstatus']) || empty($_POST['ticketstatus']))
                        throw new Exception("ticketstatus does not have a value", 1001);

                    if (!isset($_POST['reportdamage']) || empty($_POST['reportdamage']))
                        throw new Exception("reportdamage does not have a value", 1001);

                    if (!isset($_POST['trailerid']) || empty($_POST['trailerid']))
                        throw new Exception("trailerid does not have a value", 1001);

                    if (!isset($_POST['ticket_title']) || empty($_POST['ticket_title']))
                        throw new Exception("ticket_title does not have a value", 1001);

                    if ($_POST['ticketstatus'] == 'Open' && $_POST['reportdamage'] == 'No')
                        throw new Exception(
                        "Ticket can be opened for damaged trailers only", 1002
                        );

                    /** Creating Touble Ticket* */
                    $post = $_POST;
               
                    $customFields = array_flip(
                            Yii::app()->params[$this->_clientid .
                            '_custom_fields']['HelpDesk']
                    );
                 
                    foreach ($post as $k => $v) {
                        $keyToReplace = array_search($k, $customFields);
                        if ($keyToReplace) {
                            unset($post[$k]);
                            $post[$keyToReplace] = $v;
                        }
                    }
                 
                
                    //get data json 
                    $dataJson = json_encode(
                            array_merge(
                                    $post, array(
                        'parent_id' => $cacheresponse->contactId,
                        'assigned_user_id' =>$userid,
                        'ticketstatus' => (isset($post['ticketstatus']) && !empty($post['ticketstatus'])) ? $post['ticketstatus'] : 'Closed',
                                    )
                            )
                    );

               
                    //Receive response from vtiger REST service
                    //Return response to client  
                    $rest = new RESTClient();
                     $rest->format('json');
                    $response = $rest->post(
                            $this->_vtresturl, array(
                        'sessionName' => $cacheresponse->sessionName,
                        'operation' => 'create',
                        'element' => $dataJson,
                        'elementType' => $_GET['model']
                            )
                    );
                   
                  
                    if ($response == '' | $response == null)
                        throw new Exception(
                        'Blank response received from vtiger: Creating TT'
                        );

                    $globalresponse = json_decode($response);
                                 
                    /**
                     * The following section creates a response buffer
                     * 
                     */
                    //Continue to run script even when the connection is over
                    ignore_user_abort(true);
                    set_time_limit(0);

                    // buffer all upcoming output
                    ob_start();

                    $response = new stdClass();
                    $response->success = true;
                    $response->message = "Processing the request, you will be notified by mail on successfull completion";
                    $response->result = $globalresponse->result;

                    echo json_encode($response);

                    // get the size of the output
                    $size = ob_get_length();

                    // send headers to tell the browser to close the connection
                    header("Content-Length: $size");
                    header('Connection: close');

                    // flush all output
                    ob_end_flush();
                    ob_flush();
                    flush();

                    // close current session
                    if (session_id())
                        session_write_close();
                    /*                     * Creating Document* */

                    
                          if ($globalresponse->success == false)
                        throw new Exception($globalresponse->error->message);

                    //Create Documents if any is attached
                    $crmid = $globalresponse->result->id;
             
                    $globalresponse->result->documents = Array();
                    $globalresponse->result->message = Array();

                   
                    $dataJson = array(
                        'notes_title' => 'Attachement',
                        'assigned_user_id' => $userid,
                        'notecontent' => 'Attachement',
                        'filelocationtype' => 'I',
                        'filedownloadcount' => null,
                        'filestatus' => 1,
                        'fileversion' => '',
                    );

                    

                    if (!empty($_FILES) && $globalresponse->success) {

                       
                        foreach ($_FILES as $key => $file) {
                            $uniqueid = uniqid();

                            $dataJson['filename'] = $crmid . "_" . $uniqueid .
                                    "_" . $file['name'];
                            $dataJson['filesize'] = $file['size'];
                            $dataJson['filetype'] = $file['type'];

                            
                            //Upload file to Amazon S3
                            $sThree = new AmazonS3();
                            $sThree->set_region(
                                    constant("AmazonS3::" . Yii::app()->params->awsS3Region)
                            );

                            $response = $sThree->create_object(
                                    Yii::app()->params->awsS3Bucket, $crmid . '_' . $uniqueid . '_' . $file['name'], array(
                                'fileUpload' => $file['tmp_name'],
                                'contentType' => $file['type'],
                                'headers' => array(
                                    'Cache-Control' => 'max-age',
                                    'Content-Language' => 'en-US',
                                    'Expires' =>
                                    'Thu, 01 Dec 1994 16:00:00 GMT',
                                )
                                    )
                            );

                            

                            if ($response->isOK()) {


                                //Create document
                                $rest = new RESTClient();

                                $rest->format('json');
                                $document = $rest->post(
                                        $this->_vtresturl, array(
                                    'sessionName' => $cacheresponse->sessionName,
                                    'operation' => 'create',
                                    'element' =>
                                    json_encode($dataJson),
                                    'elementType' => 'Documents'
                                        )
                                );

                               

                                $document = json_decode($document);
                                if ($document->success) {
                                    $notesid = $document->result->id;

                                   

                                    //Relate Document with Trouble Ticket
                                    $rest = new RESTClient();
                                    $rest->format('json');
                                    $response = $rest->post(
                                            $this->_vtresturl, array(
                                        'sessionName' => $cacheresponse->sessionName,
                                        'operation' =>
                                        'relatetroubleticketdocument',
                                        'crmid' => $crmid,
                                        'notesid' => $notesid
                                            )
                                    );

                                   
                                    $response = json_decode($response);
                                    if ($response->success) {
                                        $globalresponse->result->documents[] = $document->result;
                                        $globalresponse->result->message[] = 'File' .
                                                ' (' . $file['name'] . ') updated.';
                                    } else {
                                        $globalresponse->result->message[] = 'not' .
                                                ' uploaded - relating ' .
                                                'document failed:' . $file['name'];
                                    }
                                } else {
                                    $globalresponse->result->message[] = 'not' .
                                            ' uploaded - creating document failed:' .
                                            $file['name'];
                                }
                            } else {
                                $globalresponse->result->message[] = 'not' .
                                        ' uploaded - upload to storage ' .
                                        'service failed:' . $file['name'];
                            }
                        }
                    }

                   

                    $globalresponse = json_encode($globalresponse);
                    $globalresponse = json_decode($globalresponse, true);

                    $customFields = Yii::app()->params[$this->_clientid .
                            '_custom_fields']['HelpDesk'];


                    unset($globalresponse['result']['update_log']);
                    unset($globalresponse['result']['hours']);
                    unset($globalresponse['result']['days']);
                    unset($globalresponse['result']['modifiedtime']);
                    unset($globalresponse['result']['from_portal']);
                    unset($globalresponse['result']['documents']);

                    foreach ($globalresponse['result'] as $fieldname => $value) {
                        $keyToReplace = array_search($fieldname, $customFields);
                        if ($keyToReplace) {
                            unset($globalresponse['result'][$fieldname]);
                            $globalresponse['result'][$keyToReplace] = $value;
                            //unset($customFields[$keyToReplace]);
                        }
                    }

                    
                   
                    


}


      
       public function actionCreateContacts() {
          

        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $contact = new Contacts;
            $response = $contact->AddContact($cacheresponse->sessionName, $this->_vtresturl,$this->_clientid, $cacheresponse->result->vtigerUserId);

            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }


           }

        public function actionDeleteContacts() {
       
          try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $contact = new Contacts;
            $response = $contact->DeleteContact($cacheresponse->sessionName, $this->_vtresturl);

            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
        
        }


   	public function actionUpdateContacts() {
          
           try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $contact = new Contacts;
            $response = $contact->EditContact($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid,$cacheresponse->result->vtigerUserId);
            
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
           		
        }

    public function actionLogin() {
        try {
            $cacheresponse = 0; //$this->checkloginusingcache();
            if (!$cacheresponse) {
                $rest = new RESTClient();
                $rest->format('json');
                $rest->set_header(
                        'Content-Type', 'application/x-www-form-urlencoded'
                );
                $response = $rest->post(
                        $this->_vtresturl . "?operation=logincustomer", "username=" . $_SERVER['HTTP_X_USERNAME'] .
                        "&password=" . $_SERVER['HTTP_X_PASSWORD']
                );
                //print_r($response);die;
                $this->_vtresponse = $response;

                if ($response == '' || $response == null)
                    throw new Exception(
                    "Blank response received from vtiger: LoginCustomer"
                    );

                $response = json_decode($response);
                if ($response->success == false)
                    throw new Exception("Invalid Username and Password");

//Store values from response
                $username = $response->result->user_name;
                $userAccessKey = $response->result->accesskey;
                $accountId = $response->result->accountId;
                $contactId = $response->result->contactId;
                $timeZone = $response->result->time_zone;
                $vtigerUserId = $response->result->vtiger_user_id;


                $usr = new UserDetail;
                $challengeToken = $usr->getchallengetoken($username, $this->_vtresturl);
                $generatedKey = md5($challengeToken . $userAccessKey);
                $session_name = $usr->getusersession($username, $generatedKey, $this->_vtresturl);
                $contactname = $usr->getcontactname($contactId, $session_name, $this->_vtresturl);
                $account = $usr->getaccountname($accountId, $session_name, $this->_vtresturl);

                $response = new stdClass();
                $response->success = true;
                $response->contactname = $contactname;
                $response->accountname = $account['accountname'];
                $response->account_no = $account['account_no'];
                $response->timeZone = $timeZone;
                $response->vtigerUserId = '19x' . $vtigerUserId;
//print_r($response);die;
                /*
                 *  Session 
                 */
                $session = new stdClass();
                $session->sessionName = $session_name;
                $session->contactId = $contactId;
		$response->vtigerUserId = $vtigerUserId;
                $session->username = $username;
                $session->result = $response;
                $this->setlogincache(json_encode($session));
                /*
                 * End
                 */
                ob_start();
                $this->_sendResponse(200, json_encode($response));
                ob_flush();
            }else {
                ob_start();
                $this->_sendResponse(200, json_encode($cacheresponse));
                ob_flush();
            }
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    function setlogincache($cacheValue) {

//echo"<pre>";       die(print_r($_SERVER));
if($_SERVER['HTTP_HOST'] == 'localhost' ){
        $cacheKey = json_encode(
                array(
                    'clientid' => $this->_clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD'],
                    'serveraddr'=>$_SERVER['HTTP_X_SERVERADDR']
                    
                )
        );
}else{

 $cacheKey = json_encode(
                array(
                    'clientid' => $this->_clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD']
                    

                )
        );


}
        Yii::app()->cache->set($cacheKey, $cacheValue, 86000);
    }

    function getlogincache() {

if($_SERVER['HTTP_HOST'] == 'localhost' ){
        $cacheKey = json_encode(
                array(
                    'clientid' => $this->_clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD'],
                   'serveraddr'=>$_SERVER['HTTP_X_SERVERADDR']
                    
                )
        ); } else {

 $cacheKey = json_encode(
                array(
                    'clientid' => $this->_clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD']
                  

                )
        );
      }
        return Yii::app()->cache->get($cacheKey);
    }

    function checkloginusingcache() {
        $cacheresponse = $this->getlogincache();
        $cacheresponse = json_decode($cacheresponse);
        if ($cacheresponse) {
            $rest = new RESTClient();
            $rest->format('json');
            $rest->set_header(
                    'Content-Type', 'application/x-www-form-urlencoded'
            );
            $response = $rest->post(
                    $this->_vtresturl . "?operation=logincustomer", "username=" . $_SERVER['HTTP_X_USERNAME'] .
                    "&password=" . $_SERVER['HTTP_X_PASSWORD']
            );

            if ($response == '' || $response == null)
                throw new Exception(
                "Blank response received from vtiger: LoginCustomer"
                );

            $response = json_decode($response);
            if ($response->success == false)
                throw new Exception("Invalid Username and Password");

            return $cacheresponse->result;
        }else {
            return false;
        }
    }

    public function actionPickuplist() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->pickuplist($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $content = json_encode(
                    array(
                        'success' => true,
                        'result' =>
                        $response
                    )
            );
            ob_start();
            $this->_sendResponse(200, $content);
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionAssets() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->assetslist($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $cachedValue = json_encode($response);
            $cachekey = $this->_clientid . '_' .
                    $_GET['model'] .
                    '_' .
                    'list';
            Yii::app()->cache->set($cachekey, $cachedValue, 86000);
            ob_start();
            $this->_sendResponse(200, $cachedValue);
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionCreateAssets() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->create($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid, $cacheresponse->result->vtigerUserId);
            ob_start();
            $this->_sendResponse(200, json_encode($response));
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionUpdateAssets() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->edit($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid, $cacheresponse->result->vtigerUserId);
            ob_start();
            $this->_sendResponse(200, json_encode($response));
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionViewAssets() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->view($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            ob_start();
            $this->_sendResponse(200, json_encode($response));
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionViewCategoryAssets() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->categorypickuplist($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            ob_start();
            $this->_sendResponse(200, json_encode($response));
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionProducts() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );

            $assets = new Assets;
            $response = $assets->productList($cacheresponse->sessionName, $this->_vtresturl);
            $cachedValue = json_encode($response);
            $cachekey = $this->_clientid . '_' .
                    $_GET['model'] .
                    '_' .
                    'list';
            Yii::app()->cache->set($cachekey, $cachedValue, 86000);
            ob_start();
            $this->_sendResponse(200, $cachedValue);
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionDamageStatus() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            if (isset($_GET['fieldname'])) {
                $damage = new Damages;
                $response = $damage->Damagelist($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
                $this->_sendResponse(200, $response);
            }
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionDamageUpdateStatus() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $damage = new Damages;
            $response = $damage->UpdateDamageStatus($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $this->_sendResponse(200, $response);
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionDamageUpdateStatusAndNotes() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $damage = new Damages;
            $response = $damage->UpdateDamageStatusAndNotes($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $this->_sendResponse(200, $response);
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionUpdatePassword() {
        try{
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $usr = new UserDetail;
            $response = $usr->UpdatePassword($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionUsers() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $usr = new UserDetail;
            $response = $usr->userlist($cacheresponse->sessionName, $this->_vtresturl);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionAccounts() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $usr = new UserDetail;
            $response = $usr->Accountlist($cacheresponse->sessionName, $this->_vtresturl);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionContacts() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $contact = new Contacts;
            
            $response = $contact->Contactlist($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);

            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionViewCategoryHelpdesk() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $helpdesk = new Helpdesk;
            $response = $helpdesk->categoryhelpdesklist($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionViewHelpdesk() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $helpdesk = new Helpdesk;
            $response = $helpdesk->view($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionExistingDamages() {
        
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $helpdesk = new Helpdesk;
            $response = $helpdesk->ExistingDamages($this->_clientid);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    public function actionViewImages() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $params = "sessionName={$cacheresponse->sessionName}" .
                    "&operation=gettroubleticketdocumentfile" .
                    "&notesid=" . $_GET['id'];


            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');
            $response = $rest->get(
                    $this->_vtresturl . "?$params"
            );

            $response = json_decode($response);

            if (!isset($_GET['path']) || $_GET['path'] == 0) {
                $sThree = new AmazonS3();
                $sThree->set_region(
                        constant("AmazonS3::" . Yii::app()->params->awsS3Region)
                );

                $uniqueId = uniqid();

                $fileResource = fopen(
                        'protected/data/' . $uniqueId .
                        $response->result->filename, 'x'
                );

                $sThreeResponse = $sThree->get_object(
                        Yii::app()->params->awsS3Bucket, $response->result->filename, array(
                    'fileDownload' => $fileResource
                        )
                );
                if (!$sThreeResponse->isOK())
                    throw new Exception("File not found.");




                $response->result->filecontent = base64_encode(
                        file_get_contents(
                                'protected/data/' . $uniqueId .
                                $response->result->filename
                        )
                );
           //     unlink(
         //               'protected/data/' . $uniqueId . $response->result->filename
            //    );

             //   $filenameSanitizer = explode("_", $response->result->filename);
             //   unset($filenameSanitizer[0]);
             //   unset($filenameSanitizer[1]);
             //   $response->result->filename = implode('_', $filenameSanitizer);
            } else {
                $response->result->filecontent = $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST'] . "/api/Images/" . $response->result->filename;
            }
            ob_start();
            $this->_sendResponse(200, json_encode($response));
            ob_flush();
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }
	
   public function actionupdate(){
		die('prakash');
	}
    public function actionCreateTicket() {
       //die(print_r($_FILES));     
      
       try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $helpdesk = new Helpdesk;
        //    $response = $helpdesk->add($cacheresponse->contactId);
	$response = $helpdesk->newadd($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid,$cacheresponse->contactId,$cacheresponse->vtigerUserId);
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout() {
        try {
            //Logout using {$this->_session->sessionName}
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $rest = new RESTClient();
            $rest->format('json');
            $response = $rest->get(
                    $this->_vtresturl .
                    "?operation=logout&sessionName=" .
                    "{$cacheresponse->sessionName}"
            );
            //Objectify the response and check its success
            $response = json_decode($response);

            if ($response->success == false)
                throw new Exception("Unable to Logout");

            $cacheKey = json_encode(
                    array(
                        'clientid' => $this->_clientid,
                        'username' => $_SERVER['HTTP_X_USERNAME'],
                        'password' => $_SERVER['HTTP_X_PASSWORD']
                    )
            );
            Yii::app()->cache->delete($cacheKey);

            //send response to client
            $response = new stdClass();
            $response->success = true;
            $this->_sendResponse(200, json_encode($response));
        } catch (Exception $ex) {
            $response = new stdClass();
            $response->success = false;
            $response->error = new stdClass();
            $response->error->code = $this->_errors[$ex->getCode()];
            $response->error->message = $ex->getMessage();
            $response->error->trace_id = $this->_traceId;
            $response->error->vtresponse = $this->_vtresponse;
            ob_start();
            $this->_sendResponse(403, json_encode($response));
            ob_flush();
        }
    }

    /**
     * Dispatches response to the client
     * 
     * Sends the final response, if the response body is blank this sends an 
     * page with the status code of the error. This also sets the http status
     * code and the MIME type of the response.
     *
     * @param int    $status       Http status code which needs to be sent
     * @param string $body         The payload
     * @param string $content_type Mime type of payload
     * 
     * @return string message body
     */
    private function _sendResponse($status = 200, $body = '', $contentType = 'text/json'
    ) {
// set the status
        $statusHeader = 'HTTP/1.1 ' .
                $status . ' ' .
                ((isset($this->_codes[$status])) ? $this->_codes[$status] : '');
        header($statusHeader);

// and the content type
        header('Content-type: ' . $contentType);
        header('Access-Control-Allow-Origin: *');

// pages with body are easy
        if ($body != '') {
// send the body
            echo $body;
            header('Content-Length: ' . strlen($body));
            header("Connection: Close");
        } else {
            $message = '';
            switch ($status) {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI']
                            . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error ' .
                            'processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ?
                    $_SERVER['SERVER_SOFTWARE'] . ' Server at ' .
                    $_SERVER['SERVER_NAME'] . ' Port ' .
                    $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

// this should be templated in a real-world solution
            $body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" ' .
                    '"http://www.w3.org/TR/html4/strict.dtd">
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; ' .
                    'charset=iso-8859-1">
                <title>' . $status . ' ' . ((isset($this->_codes[$status])) ?
                            $codes[$status] : '') . '</title>
            </head>
            <body>
                <h1>' .
                    ((isset($this->_codes[$status])) ? $codes[$status] : '') .
                    '</h1>
                <h2> Trace ID:' . $this->_traceId . '</h2>
                <p>' . $message . '</p>
                <hr />
                <address>' . $signature . '</address>
            </body>
            </html>';

            echo $body;
        }

//Log
        Yii::log(
                "TRACE(" . $this->_traceId . "); FUNCTION(" . __FUNCTION__ .
                "); DISPATCH RESPONSE: " . $body, CLogger::LEVEL_TRACE
        );

        Yii::app()->end();
    }

}
