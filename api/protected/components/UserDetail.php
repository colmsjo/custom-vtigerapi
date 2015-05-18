<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UserDetail
 *
 * @author prakash
 */
class UserDetail extends CApplicationComponent {

    public function getchallengetoken($username, $vtresturl) {
        $rest = new RESTClient();
        $rest->format('json');

//Login using $username and $userAccessKey
        $response = $rest->get(
                $vtresturl .
                "?operation=getchallenge&username=$username"
        );

        if ($response == '' || $response == null)
            throw new Exception(
            "Blank response received from vtiger: GetChallenge"
            );

//Objectify the response and check its success
        $response = json_decode($response);
        if ($response->success == false)
            throw new Exception("Unable to get challenge token");

//Store values from response
        return $response->result->token;
    }

    public function getusersession($username, $generatedKey, $vtresturl) {
        $rest = new RESTClient();
        $rest->format('json');

//Login using the generated key
        $response = $rest->post(
                $vtresturl .
                "?operation=login", "username=$username&accessKey=$generatedKey"
        );

        if ($response == '' || $response == null)
            throw new Exception(
            "Blank response received from vtiger: Login"
            );

//Objectify the response and check its success
        $response = json_decode($response);
        if ($response->success == false)
            throw new Exception("Invalid generated key");

        return $response->result->sessionName;
    }

    public function getcontactname($contactId, $session_name, $vtresturl) {
        $query = "select * from Contacts" .
                " where id = " . $contactId . ";";

//urlencode to as its sent over http.
        $queryParam = urlencode($query);

//creating query string
        $params = "sessionName={$session_name}" .
                "&operation=query&query=$queryParam";

        $rest = new RESTClient();
        $rest->format('json');
        $contact = $rest->get(
                $vtresturl . "?$params");

        if ($contact == '' || $contact == null)
            throw new Exception(
            "Blank response received from vtiger: Contact"
            );
        $contact = json_decode($contact, true);

        if (!$contact['success'])
            throw new Exception($contact['error']['message']);

        return $contact['result'][0]['firstname'] .
                " " . $contact['result'][0]['lastname'];
    }

    public function getaccountname($accountId, $session_name, $vtresturl) {
        $query = "select accountname, account_no from Accounts" .
                " where id = " .
                $accountId . ";";

//urlencode to as its sent over http.
        $queryParam = urlencode($query);

//creating query string
        $params = "sessionName={$session_name}" .
                "&operation=query&query=$queryParam";

//sending request to vtiger REST Service 
        $rest = new RESTClient();
        $rest->format('json');
        $account = $rest->get($vtresturl . "?$params");
//Save vtiger response
        if ($account == '' || $account == null)
            throw new Exception(
            "Blank response received from vtiger: Account"
            );

//Objectify the response and check its success
        $account = json_decode($account, true);
        if (!$account['success']) {
            throw new Exception($account['error']['message']);
        }
        return $account['result'][0];
    }

    public function UpdatePassword($sessionName, $vtresturl, $clientid) {
        if ($_GET['action'] == 'reset') {


            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');
            $response = $rest->post(
                    $vtresturl, array(
                'operation' => 'resetpassword',
                'username' => $_SERVER['HTTP_X_USERNAME'],
                    )
            );

            $response = json_decode($response);

            if ($response->success == false)
                throw new Exception("Unable to reset password");

            //Create a cache key for saving session
            $keyToDelete = json_encode(
                    array(
                        'clientid' => $clientid,
                        'username' => $_SERVER['HTTP_X_USERNAME'],
                        'password' => $response->result->oldpassword
                    )
            );
        }
        if ($_GET['action'] == 'changepw') {
            $_PUT = Array();
            parse_str(file_get_contents('php://input'), $_PUT);
            if (!isset($_PUT['newpassword']))
                throw new Exception('New Password not provided.');

            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();
            $rest->format('json');
            $response = $rest->post(
                    $vtresturl, array(
                    'sessionName' => $sessionName,
                    'operation' => 'changepw',
                    'username' => $_SERVER['HTTP_X_USERNAME'],
                    'oldpassword' => $_SERVER['HTTP_X_PASSWORD'],
                    'newpassword' => $_PUT['newpassword']
                    )
            );
            //echo '<pre>';
            echo $_SERVER['HTTP_X_USERNAME'];
            echo  $_SERVER['HTTP_X_PASSWORD'];
            echo $_PUT['newpassword'];
            print_r($response);die;
            $response = json_decode($response);
            if ($response->success == false)
                throw new Exception($response->error->message);

            return $response;
        }
    }

}
