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

    public function actionLogin() {
        try {
            $cacheresponse = 0;//$this->checkloginusingcache();
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
                $response->vtigerUserId = '19x'.$vtigerUserId;
//print_r($response);die;
                /*
                 *  Session 
                 */
                $session = new stdClass();
                $session->sessionName = $session_name;
                $session->contactId = $contactId;
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
        $cacheKey = json_encode(
                array(
                    'clientid' => $this->_clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD']
                )
        );
        Yii::app()->cache->set($cacheKey, $cacheValue, 86000);
    }

    function getlogincache() {
        $cacheKey = json_encode(
                array(
                    'clientid' => $this->_clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD']
                )
        );
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
        try {
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

    public function actionCreateTicket() {
        try {
            $cacheresponse = $this->getlogincache();
            $cacheresponse = json_decode($cacheresponse);
            if (!$cacheresponse)
                throw new Exception(
                "Not a Valid Request."
                );
            $helpdesk = new Helpdesk;
            $response = $helpdesk->add($cacheresponse->sessionName, $this->_vtresturl, $this->_clientid, $cacheresponse);
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
