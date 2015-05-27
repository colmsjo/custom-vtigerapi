<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AssetsController
 *
 * @author prakash
 */
class AssetsController extends Controller {

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
            echo 'hello';die;
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

    public function actionPickuplist() {
        try {
            $loginuser = new UserIdentity;
            $cacheresponse = $loginuser->getlogincache($this->_clientid);
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
            $loginuser = new UserIdentity;
            $cacheresponse = $loginuser->getlogincache($this->_clientid);
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

    public function actionProducts() {
        try {
            $loginuser = new UserIdentity;
            $cacheresponse = $loginuser->getlogincache($this->_clientid);
            echo 'a';die;
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

}