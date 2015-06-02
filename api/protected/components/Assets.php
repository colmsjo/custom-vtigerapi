<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Assets
 *
 * @author prakash
 */
class Assets extends CApplicationComponent {

    public function categorypickuplist($sessionName, $vtresturl, $clientid) {

        if (isset($_GET['category'])) {
            if ($_GET['category'] == 'inoperation') {
                $query = "select * from " . $_GET['model'] .
                        " where assetstatus = 'In Service';";
            } else {
                $query = "select * from " . $_GET['model'] .
                        " where assetstatus = 'Out-of-service';";
            }
        } else {
            $query = "select * from " . $_GET['model'] . ";";
        }

        //urlencode to as its sent over http.
        $queryParam = urlencode($query);
        //$queryParam = $query;

        //creating query string
        $params = "sessionName={$sessionName}" .
                "&operation=query&query=$queryParam";
       

        //Receive response from vtiger REST service
        //Return response to client  
        $rest = new RESTClient();

        $rest->format('json');
       
        //echo  $vtresturl . "?$params";die;
        $response = $rest->get(
                $vtresturl . "?$params"
        );


        if ($response == '' || $response == null)
            throw new Exception(
            "Blank response received from " .
            "vtiger: Get Asset List"
            );

        //Objectify the response and check its success
        $response = json_decode($response,true);
        
        
        if ($response['success'] == false)
            throw new Exception('Unable to fetch details');

        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['Assets'];

        //Before sending response santise custom fields names to 
        //human readable field names                
        foreach ($response['result'] as &$asset) {
            unset($asset['update_log']);
            unset($asset['hours']);
            unset($asset['days']);
            unset($asset['modifiedtime']);
            unset($asset['from_portal']);
            foreach ($asset as $fieldname => $value) {
                $keyToReplace = array_search(
                        $fieldname, $customFields
                );
                if ($keyToReplace) {
                    unset($asset[$fieldname]);
                    $asset[$keyToReplace] = $value;
                    //unset($customFields[$keyToReplace]);
                }
            }
        }

        return $response;
    }

    public function pickuplist($sessionName, $vtresturl, $clientid) {

      
        if (isset($_GET['fieldname'])) {

            $flippedCustomFields = array_flip(Yii::app()->params[$clientid .
                    '_custom_fields']['Assets']);

            //Check if the requested field name is a vtiger
            //custom field
            if (in_array($_GET['fieldname'], $flippedCustomFields)) {
                $fieldname = Yii::app()->params[$clientid .
                        '_custom_fields'][$_GET['model']][$_GET['fieldname']];
            } else {
                $fieldname = $_GET['fieldname'];
            }

            $params = "sessionName={$sessionName}" .
                    "&operation=describe" .
                    "&elementType=" . $_GET['model'];

            //Send request to vtiger
            $rest = new RESTClient();

            $rest->format('json');
            $response = $rest->get(
                    $vtresturl . "?$params"
            );
            if ($response == '' || $response == null)
                throw new Exception("Blank response received from" .
                " vtiger: Asset Picklist");

            //Objectify the response and check its success
            $response = json_decode($response, true);

            if ($response['success'] == false)
                throw new Exception('Fetching details failed');

            //Find the appropriate field whose label value needs to
            //be sent  
            foreach ($response['result']['fields'] as $field) {

                if ($fieldname == $field['name']) {

                    //Check if the field is a picklist
                    if ($field['type']['name'] == 'picklist') {

                        //Loop through all values of the pick list
                        foreach ($field['type']['picklistValues'] as &$option)

                        //Check if there is a dependency setup
                        //for the picklist value
                            if (isset($option['dependency'])) {

                                foreach ($option['dependency'] as $depFieldname => $dependency) {
                                    if (in_array($depFieldname, Yii::app()->params[$clientid . '_custom_fields']['Assets'])) {
                                        $newFieldname = $flippedCustomFields[$depFieldname];
                                        $option['dependency'][$newFieldname] = $option['dependency'][$depFieldname];
                                        unset($option['dependency'][$depFieldname]);
                                    }
                                }
                            }

                        //Dispatch the response
                        return $field['type']['picklistValues'];

                        //eject 2 levels
                        break 2;
                    }
                    throw new Exception("Not an picklist field");
                }
            }
            throw new Exception("Fieldname not found");
        } else {
            if (isset($_GET['category'])) {
                if ($_GET['category'] == 'inoperation') {
                    $query = "select * from " . $_GET['model'] .
                            " where assetstatus = 'In Service';";
                } else {
                    $query = "select * from " . $_GET['model'] .
                            " where assetstatus = 'Out-of-service';";
                }
            } else {
                $query = "select * from " . $_GET['model'] . ";";
            }
        }
    }

    public function assetslist($sessionName, $vtresturl, $clientid) {

        if (isset($_GET['actionType'])) {
            if ($_GET['actionType'] == 'search') {
                $searchData = $_GET['searchString'];
                if ($searchData == 'None') {
                    $query = "select * from " . $_GET['model'] . " ;";
                } else {
                    $query = "select * from " . $_GET['model'] .
                            " where " . base64_decode($searchData) . " ;";
                }
            } else {
                throw new Exception("Action search not found!");
            }
        } else {
            $query = "select * from " . $_GET['model'] . ";";
        }
        $queryParam = urlencode($query);
        $params = "sessionName={$sessionName}" .
                "&operation=query&query=$queryParam";
        $rest = new RESTClient();


        //echo $vtresturl . "?$params";die;
        $rest->format('json');
        $response = $rest->get(
                $vtresturl . "?$params"
        );
        print_r($response);
        die;
        $response = json_decode($response, true);

        if ($response['success'] == false)
            throw new Exception('Unable to fetch details');

        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['Assets'];
        foreach ($response['result'] as &$asset) {
            unset($asset['update_log']);
            unset($asset['hours']);
            unset($asset['days']);
            unset($asset['modifiedtime']);
            unset($asset['from_portal']);
            foreach ($asset as $fieldname => $value) {
                $keyToReplace = array_search(
                        $fieldname, $customFields
                );
                if ($keyToReplace) {
                    unset($asset[$fieldname]);
                    $asset[$keyToReplace] = $value;
//unset($customFields[$keyToReplace]);
                }
            }
        }

        return $response;
    }

    public function productList($sessionName, $vtresturl) {

        $query = "select * from " . $_GET['model'] . ";";
        $queryParam = urlencode($query);

        //creating query string
        $params = "sessionName={$sessionName}" .
                "&operation=query&query=$queryParam";


        $rest = new RESTClient();
        $rest->format('json');
        $response = $rest->get(
                $vtresturl . "?$params"
        );


        if ($response == '' || $response == null)
            throw new Exception(
            "Blank response received from " .
            "vtiger: Get Products List"
            );

        //Objectify the response and check its success
        $response = json_decode($response, true);

        if ($response['success'] == false)
            throw new Exception('Unable to fetch details');

        //Before sending response santise custom fields names to 
        //human readable field names                
        return $response;
    }

    public function create($sessionName, $vtresturl, $clientid, $userid) {
        $scriptStarted = date("c");
        if (!isset($_POST['assetname']) || empty($_POST['assetname']))
            throw new Exception("asset name does not have a value", 1001);

        if (!isset($_POST['serialnumber']) || empty($_POST['serialnumber']))
            throw new Exception("serial number does not have a value", 1001);

        if (!isset($_POST['trailertype']) || empty($_POST['trailertype']))
            throw new Exception("trailer type does not have a value", 1001);

        if (!isset($_POST['product']) || empty($_POST['product']))
            throw new Exception("product does not have a value", 1001);

        if (!isset($_POST['account']) || empty($_POST['account']))
            throw new Exception("customer name does not have a value", 1001);

        /** Creating Assets* */
        $post = $_POST;
        $customFields = array_flip(
                Yii::app()->params[$clientid .
                '_custom_fields']['Assets']
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
            'assigned_user_id' => $userid
                        )
                )
        );

        //Receive response from vtiger REST service
        //Return response to client  
        $rest = new RESTClient();

        $rest->format('json');
        $response = $rest->post(
                $vtresturl, array(
            'sessionName' => $sessionName,
            'operation' => 'create',
            'element' => $dataJson,
            'elementType' => $_GET['model']
                )
        );

        if ($response == '' | $response == null)
            throw new Exception(
            'Blank response received from vtiger: Creating TT'
            );
        Yii::app()->cache->delete(
                $clientid . '_' .
                $_GET['model']
                . '_'
                . 'list'
        );
        //Objectify the response and check its success
        $response = json_decode($response, true);

        if ($response['success'] == false)
            throw new Exception('Unable to fetch details');

        return $response;
    }

    public function edit($sessionName, $vtresturl, $clientid, $userId) {
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'update') {

                /**
                 * Validations
                 */
                $_PUT = Array();
                parse_str(file_get_contents('php://input'), $_PUT);
                $_POST = $_PUT;

                $scriptStarted = date("c");
                if (!isset($_POST['assetname']) || empty($_POST['assetname'])
                )
                    throw new Exception("asset name does not have a value", 1001);

                if (!isset($_POST['serialnumber']) || empty($_POST['serialnumber'])
                )
                    throw new Exception("serial number does not have a value", 1001);

                if (!isset($_POST['trailertype']) || empty($_POST['trailertype'])
                )
                    throw new Exception("trailer type does not have a value", 1001);

                if (!isset($_POST['product']) || empty($_POST['product'])
                )
                    throw new Exception("product does not have a value", 1001);
                if (!isset($_POST['account']) || empty($_POST['account'])
                )
                    throw new Exception("customer name does not have a value", 1001);



                /** Updating Assets* */
                $post = $_POST;
                $customFields = array_flip(
                        Yii::app()->params[$clientid .
                        '_custom_fields']['Assets']
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
                    'assigned_user_id' => $userId
                                )
                        )
                );
                print_r($dataJson);die;
                //Receive response from vtiger REST service
                //Return response to client  
                $rest = new RESTClient();
                $rest->format('json');
                $response = $rest->post(
                        $vtresturl, array(
                    'sessionName' => $sessionName,
                    'operation' => 'update',
                    'element' => $dataJson
                        )
                );


                if ($response == '' | $response == null)
                    throw new Exception(
                    'Blank response received from vtiger: Creating TT'
                    );
                //Objectify the response and check its success
                $response = json_decode($response, true);
                Yii::app()->cache->delete(
                        $clientid . '_' .
                        $_GET['model']
                        . '_'
                        . 'list'
                );
                if ($response['success'] == false)
                    throw new Exception('Unable to fetch details');

                return $response;
            }
        } else {
            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');

            $_PUT = Array();
            parse_str(file_get_contents('php://input'), $_PUT);

            $response = $rest->get(
                    $vtresturl, array(
                'sessionName' => $sessionName,
                'operation' => 'retrieve',
                'id' => $_GET['id']
                    )
            );


            $response = json_decode($response, true);

            //get data json 
            $retrivedObject = $response['result'];
            if ($_PUT['assetstatus'] == 'In Service')
                $retrivedObject['assetstatus'] = 'In Service';
            else
                $retrivedObject['assetstatus'] = 'Out-of-service';


            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');
            $response = $rest->post(
                    $vtresturl, array(
                'sessionName' => $sessionName,
                'operation' => 'update',
                'element' => json_encode($retrivedObject)
                    )
            );

            $response = json_decode($response, true);

            if ($response['success'] == false)
                throw new Exception($response['error']['message']);

            $customFields = Yii::app()->params[$clientid .
                    '_custom_fields']['Assets'];

            unset($response['result']['update_log']);
            unset($response['result']['hours']);
            unset($response['result']['days']);
            unset($response['result']['modifiedtime']);
            unset($response['result']['from_portal']);

            foreach ($response['result'] as $fieldname => $value) {
                $keyToReplace = array_search($fieldname, $customFields);
                if ($keyToReplace) {
                    unset($response['result'][$fieldname]);
                    $response['result'][$keyToReplace] = $value;
                }
            }

            return $response;
        }
    }

    public function delete($sessionName, $vtresturl, $clientid) {
        $id = $_GET['id'];
        $rest = new RESTClient();
        $rest->format('json');
        $response = $rest->post(
                $vtresturl, array(
            'sessionName' => $sessionName,
            'operation' => 'delete',
            'id' => $id
                )
        );
        if ($response == '' || $response == null)
            throw new Exception(
            "Blank response received from " .
            "vtiger: Get Products List"
            );
        //Objectify the response and check its success
        $response = json_decode($response, true);
        if ($response['success'] == false)
            throw new Exception('Unable to delete assests');
        Yii::app()->cache->delete(
                $clientid . '_' .
                $_GET['model']
                . '_'
                . 'list'
        );
        //Send the response
        return $response;
    }

    public function view($sessionName, $vtresturl, $clientid) {
        if (preg_match('/[0-9]?x[0-9]?/i', $_GET['id']) == 0)
            throw new Exception('Invalid format of Id');

        //Send request to vtiger REST service
        $query = "select * from " . $_GET['model'] .
                " where id = " . $_GET['id'] . ";";

        //urlencode to as its sent over http.
        $queryParam = urlencode($query);

        //creating query string
        $params = "sessionName={$sessionName}" .
                "&operation=query&query=$queryParam";

        //Receive response from vtiger REST service
        //Return response to client  
        $rest = new RESTClient();

        $rest->format('json');
        $response = $rest->get(
                $vtresturl . "?$params"
        );

        $response = json_decode($response, true);
        $response['result'] = $response['result'][0];

        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['Assets'];

        foreach ($response['result'] as $fieldname => $value) {
            $keyToReplace = array_search($fieldname, $customFields);
            if ($keyToReplace) {
                unset($response['result'][$fieldname]);
                $response['result'][$keyToReplace] = $value;
                //unset($customFields[$keyToReplace]); 
            }
        }
        return $response;
    }

}
