<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CApplicationComponent {

    /**
     * Authenticates a user.
     * The example implementation makes sure if the username and password
     * are both 'demo'.
     * In practical applications, this should be changed to authenticate
     * against some persistent user identity storage (e.g. database).
     * @return boolean whether authentication succeeds.
     */
    
    
    function setlogincache($cacheValue,$clientid) {
        $cacheKey = json_encode(
                array(
                    'clientid' => $clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD']
                )
        );
        Yii::app()->cache->set($cacheKey, $cacheValue, 86000);
    }

    function getlogincache($clientid) {
        $cacheKey = json_encode(
                array(
                    'clientid' => $clientid,
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'password' => $_SERVER['HTTP_X_PASSWORD']
                )
        );
        return Yii::app()->cache->get($cacheKey);
    }

    function checkloginusingcache($vtresturl) {
        $cacheresponse = $this->getlogincache();
        $cacheresponse = json_decode($cacheresponse);
        if ($cacheresponse) {
            $rest = new RESTClient();
            $rest->format('json');
            $rest->set_header(
                    'Content-Type', 'application/x-www-form-urlencoded'
            );
            $response = $rest->post(
                    $vtresturl . "?operation=logincustomer", "username=" . $_SERVER['HTTP_X_USERNAME'] .
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

}
