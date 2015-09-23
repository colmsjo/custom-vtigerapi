<?php

class Contacts extends CApplicationComponent {

    public function AddContact($sessionName, $vtresturl, $clientid, $userid) {
           $userid='19x'.$userid;
          $scriptStarted = date("c");

        if (!isset($_POST['lastname']) || empty($_POST['lastname']))
            throw new Exception("last name does not have a value", 1001);

        if (!isset($_POST['email']) || empty($_POST['email']))
            throw new Exception("Email does not have a value", 1001);

        if (!isset($_POST['account_id']) || empty($_POST['account_id']))
            throw new Exception("account does not have a value", 1001);

        /** Creating Assets* */
        $post = $_POST;
       
       
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
            'Blank response received from vtiger: Creating Contact'
            );

        //Objectify the response and check its success
        $response = json_decode($response, true);
        
        if ($response['success'] == false)
            throw new Exception('Unable to save contacts');

        return $response;
    }

    public function EditContact($sessionName, $vtresturl, $clientid, $userId) {
      $userId='19x'.$userId;
   
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'update') {
                
                /**
                 * Validations
                 */
                $_PUT = Array();
                parse_str(file_get_contents('php://input'), $_PUT);
                $_POST = $_PUT;
              // print_r($_POST);die;
                $scriptStarted = date("c");
                if (!isset($_POST['lastname']) || empty($_POST['lastname']))
                    throw new Exception("last name does not have a value", 1001);

                if (!isset($_POST['email']) || empty($_POST['email']))
                    throw new Exception("Email does not have a value", 1001);

                if (!isset($_POST['account_id']) || empty($_POST['account_id']))
                    throw new Exception("account does not have a value", 1001);
                /** Updating Assets* */
               
                $post = $_POST;
               
               
                $dataJson = json_encode(
                        array_merge(
                                $post, array(
                    'assigned_user_id' => $userId
                                )
                        )
                );
                //get data json
                 
            ;
                  
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
               // $this->_vtresponse = $response;

                //Objectify the response and check its success
                 $response = json_decode($response, true);
  //             print_r($response);die;
                if ($response['success'] == false)
                    throw new Exception('Unable to update contacts');

                return $response;
            }
        } else {
            throw new Exception("Invalid Request.", 1001);
        }
    }

    public function DeleteContact($sessionName, $vtresturl) {
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
        //Save vtiger response
       // $this->_vtresponse = $response;
        //Objectify the response and check its success
        $response = json_decode($response, true);
       
        if ($response['success'] == false)
            throw new Exception('Unable to delete contacts');

        return $response;
    }

    public function Contactlist($sessionName, $vtresturl, $clientid) {
     
        if (isset($_GET['actionType'])) {
            if ($_GET['actionType'] == 'search') {
                $searchData = $_GET['searchString'];
                if ($searchData == 'None') {
                    $query = "select count(*) from " . $_GET['model'] . " ;";
                } else {
                    $query = "select count(*) from " . $_GET['model'] .
                            " where " . base64_decode($searchData) . " ;";
                }
 	    } else {
                throw new Exception("Action search not found!");
            }

	    $queryParam = urlencode($query);
            $params = "sessionName={$sessionName}" .
                           "&operation=query&query=$queryParam";
            $rest = new RESTClient();
            $rest->format('json');
            $response = $rest->get(
                             $vtresturl . "?$params"
                        );
            $response = json_decode($response, true);
            $count = ceil($response['result'][0]['count']/100);
            $i=0;
            $result = array();
            while($i<($count*100)){
			if ($searchData == 'None') {
                    		$query = "select * from " . $_GET['model'] . " limit " .$i .  ",".($i+100)." ;";
               		} else {
                    		$query = "select * from " . $_GET['model'] .
                            		" where " . base64_decode($searchData) . " limit " .$i .  ",".($i+100)." ;";
                	}
			$queryParam = urlencode($query);
                        $params = "sessionName={$sessionName}" .
                                 "&operation=query&query=$queryParam";
                        $rest = new RESTClient();
                        $rest->format('json');
                        $response = $rest->get(
                                           $vtresturl . "?$params"
                                     );
                        $response = json_decode($response, true);
                        $result =array_merge($response['result'],$result);
                        $i=$i+100;
		}
            $response['result'] = $result;

            

            if ($response['success'] == false)
                throw new Exception('Unable to fetch details');

            //Before sending response santise custom fields names to 
            //human readable field names
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

        } else {
           
            if(!isset($_GET['id']) && empty($_GET['id'])){
           	$query = "select count(*) from " . $_GET['model'] . ";";
		             $queryParam = urlencode($query);
		$queryParam = urlencode($query);
                $params = "sessionName={$sessionName}" .
                            "&operation=query&query=$queryParam";

                $rest = new RESTClient();
                $rest->format('json');
                $response = $rest->get(
                                 $vtresturl . "?$params"
                            );
                $response = json_decode($response, true);
                $count = ceil($response['result'][0]['count']/100);
                $i=0;
		$result = array();
		while($i<($count*100)){
        	        $query = "select * from " . $_GET['model'] . " limit " .$i .  ",".($i+100).";";
                        $queryParam = urlencode($query);
                        $params = "sessionName={$sessionName}" .
                        	  "&operation=query&query=$queryParam";
                        $rest = new RESTClient();
                        $rest->format('json');
                        $response = $rest->get(
                                          $vtresturl . "?$params"
                                    );
                        $response = json_decode($response, true);
                        $result =array_merge($response['result'],$result);
                        $i=$i+100;
                }
                $response['result'] = $result;
     
            } else if($_GET['id']!=''){
     
        	  $query = "select * from " . $_GET['model'] .
                            " where id =" . $_GET['id'] . " ;";
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
               			 "vtiger: Get Contacts list"
                	);

        	    //Objectify the response and check its success
	            $response = json_decode($response, true);

            	}
	     	

            if ($response['success'] == false)
                throw new Exception('Unable to fetch details');

            //Before sending response santise custom fields names to 
            //human readable field names    
        }
        //Send the response
        return $response;
    }

}
