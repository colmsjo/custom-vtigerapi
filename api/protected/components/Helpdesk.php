<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Helpdesk
 *
 * @author prakash
 */
class Helpdesk extends CApplicationComponent {

    /**
     * The vTiger REST Web Services Entities
     */
    private $_wsEntities = Array(
        'Documents' => 15,
        'Contacts' => 12
    );

    public function categoryhelpdesklist($sessionName, $vtresturl, $clientid) {

        //Send request to vtiger REST service
        if (isset($_GET['category'])) {
            $query = "select * from " . $_GET['model'];
            //creating where clause based on parameters
            $whereClause = Array();
            if ($_GET['category'] == 'inoperation') {
                $whereClause[] = "ticketstatus = 'Closed'";
            }
            if ($_GET['category'] == 'damaged') {
                $whereClause[] = "ticketstatus = 'Open'";
            }

            if (isset($_GET['reportdamage']))
                if ($_GET['reportdamage'] != 'all') {
                    $whereClause[] = Yii::app()->params[$clientid . '_custom_fields'][$_GET['model']]['reportdamage'] .
                            " = '" . ucwords($_GET['reportdamage']) . "'";
                }

            //Adding date range filter
            if (isset($_GET['year']) && isset($_GET['month'])) {
                if ($_GET['year'] != '0000') {
                    if ($_GET['month'] == '00') {
                        $startmonth = '01';
                        $endmonth = '12';
                    } else {
                        $startmonth = $_GET['month'];
                        $endmonth = $_GET['month'];
                    }
                    if (!checkdate($startmonth, "01", $_GET['year']))
                        throw new Exception(
                        "Invalid month specified in list criteria"
                        );
                    $whereClause[] = "createdtime >= '" .
                            $_GET['year'] . "-" . $startmonth . "-01'";
                    $whereClause[] = "createdtime <= '" .
                            $_GET['year'] . "-" . $endmonth . "-31'";
                }
            }
            //Adding trailer filter
            if (isset($_GET['trailerid'])) {
                if ($_GET['trailerid'] != '0')
                    $whereClause[] = Yii::app()->params[$clientid . '_custom_fields']
                            ['HelpDesk']['trailerid'] .
                            " = '" . $_GET['trailerid'] . "'";
            }

            if (isset($_GET['ticketstatus'])) {
                $ticketst = $_GET['ticketstatus'];
                $whereClause[] = "ticketstatus = '$ticketst'";
            } else {
                $ticketstatus = ' ';
            }


            //Attaching where clause to filter
            if (count($whereClause) != 0)
                $query = $query . " where " .
                        implode(" and ", $whereClause);

            //Terminating the query
            $limit = '';
            if (isset($_GET['minLimit']) && isset($_GET['maxLimit'])) {
                $minLimit = $_GET['minLimit'];
                $maxLimit = $_GET['maxLimit'];
                $limit = " limit $minLimit, $maxLimit";
            }
            $query = $query . " order by id desc $limit;";

            // echo $query;die;
            //urlencode to as its sent over http.
            $queryParam = urlencode($query);

            //creating query string
            $params = "sessionName={$sessionName}" .
                    "&operation=query&query=$queryParam";



            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');
            //echo $vtresturl . "?$params";die;
            $response = $rest->get(
                    $vtresturl . "?$params"
            );



            //Objectify the response and check its success
            $response = json_decode($response, true);

            if ($response['success'] == false)
                throw new Exception('Fetching details failed');

            //Get Accounts List
            $query = "select * from Accounts;";

            //urlencode to as its sent over http.
            $queryParam = urlencode($query);

            //creating query string
            $params = "sessionName={$sessionName}" .
                    "&operation=query&query=$queryParam";



            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');
            $accounts = $rest->get(
                    $vtresturl . "?$params"
            );



            //Objectify the response and check its success
            $accounts = json_decode($accounts, true);

            if ($accounts['success'] == true) {
                $tmpAccounts = array();
                if (isset($accounts['result']))
                    foreach ($accounts['result'] as $account)
                        $tmpAccounts[$account['id']] = $account['accountname'];
            }


            //Get Contact List
            $query = "select * from Contacts;";

            //urlencode to as its sent over http.
            $queryParam = urlencode($query);

            //creating query string
            $params = "sessionName={$sessionName}" .
                    "&operation=query&query=$queryParam";



            //Receive response from vtiger REST service
            //Return response to client  
            $rest = new RESTClient();

            $rest->format('json');
            $contacts = $rest->get(
                    $vtresturl . "?$params"
            );



            //Objectify the response and check its success
            $contacts = json_decode($contacts, true);
            if ($contacts['success'] == true) {
                $tmpContacts = array();

                if (isset($contacts['result']))
                    foreach ($contacts['result'] as $contact) {

                        $tmpContacts[$contact['id']]['contactname'] = $contact['firstname'] . ' ' .
                                $contact['lastname'];
                        if (!empty($contact['account_id']))
                            $tmpContacts[$contact['id']]['accountname'] = $tmpAccounts[$contact['account_id']];
                        else
                            $tmpContacts[$contact['id']]['accountname'] = '';
                    }
            }

            //Before sending response santise custom fields names to 
            //human readable field names
            $customFields = Yii::app()->params[$clientid .
                    '_custom_fields']['HelpDesk'];

            foreach ($response['result'] as &$troubleticket) {
                unset($troubleticket['update_log']);
                unset($troubleticket['hours']);
                unset($troubleticket['days']);
                unset($troubleticket['modifiedtime']);
                unset($troubleticket['from_portal']);
                if (isset($tmpContacts)) {
                    if (isset($tmpContacts[$troubleticket['parent_id']])) {
                        $troubleticket['contactname'] = $tmpContacts[$troubleticket['parent_id']]['contactname'];
                        $troubleticket['accountname'] = $tmpContacts[$troubleticket['parent_id']]['accountname'];
                    } else {
                        $troubleticket['contactname'] = '';
                        $troubleticket['accountname'] = '';
                    }
                }
                foreach ($troubleticket as $fieldname => $value) {
                    $keyToReplace = array_search(
                            $fieldname, $customFields
                    );
                    if ($keyToReplace) {
                        unset($troubleticket[$fieldname]);
                        $troubleticket[$keyToReplace] = $value;
                        //unset($customFields[$keyToReplace]);
                    }
                }
            }
        }
        return $response;
    }

    public function view($sessionName, $vtresturl, $clientid) {
        if (preg_match('/[0-9]?x[0-9]?/i', $_GET['id']) == 0)
            throw new Exception('Invalid format of Id');

        //Get HelpDesk details 
        //Creating vTiger Query
        $query = "select * from " . $_GET['model'] .
                " where id = " . $_GET['id'] . ";";

        //urlencode to as its sent over http.
        $queryParam = urlencode($query);

        //creating query string
        $params = "sessionName={$sessionName}" .
                "&operation=query&query=$queryParam";



        //sending Request vtiger REST service
        $rest = new RESTClient();

        $rest->format('json');
        $response = $rest->get(
                $vtresturl . "?$params"
        );



        //Objectify the response and check its success
        $response = json_decode($response, true);
        $response['result'] = $response['result'][0];

        if (!$response['success'])
            throw new Exception($response['error']['message']);

        //Get Documents Ids
        //creating query string
        $params = "sessionName={$sessionName}" .
                "&operation=getrelatedtroubleticketdocument" .
                "&crmid=" . $_GET['id'];



        //sending request vtiger REST service
        $rest = new RESTClient();

        $rest->format('json');
        // echo $vtresturl . "?$params";
        //die;
        $documentids = $rest->get(
                $vtresturl . "?$params"
        );



        //Arrayfy the response and check its success 
        $documentids = json_decode($documentids, true);
        if ($documentids['success'] == false)
            throw new Exception('Unable to fetch Documents');

        $documentids = $documentids['result'];

        // Get Document Details 
        if (count($documentids) != 0) {

            //Building query for fetching documents
            $query = "select * from Documents" .
                    " where id in (" . $this->_wsEntities['Documents']
                    . "x" .
                    implode(
                            ", " . $this->_wsEntities['Documents']
                            . "x", $documentids
                    ) . ");";

            //urlencode to as its sent over http.
            $queryParam = urlencode($query);

            //creating query string
            $params = "sessionName={$sessionName}" .
                    "&operation=query&query=$queryParam";



            //sending request to vtiger REST Service 
            $rest = new RESTClient();

            $rest->format('json');
            $documents = $rest->get(
                    $vtresturl . "?$params"
            );



            //Objectify the response and check its success
            $documents = json_decode($documents, true);

            if (!$documents['success'])
                throw new Exception($documents['error']['message']);

            $response['result']['documents'] = $documents['result'];

            foreach ($response['result']['documents'] as $k => $doc) {
                //creating query string
                $params = "sessionName={$sessionName}" .
                        "&operation=gettroubleticketdocumentfile" .
                        "&notesid=" . $doc['id'];


                //Receive response from vtiger REST service
                //Return response to client  
                $rest = new RESTClient();

                $rest->format('json');
                $respo = $rest->get(
                        $vtresturl . "?$params"
                );
                $respo = json_decode($respo, true);
                if ($respo['success'])
                    $response['result']['documents'][$k]['file'] = $respo['result'];
            }
        }

        /* Get Contact's Name */
        if ($response['result']['parent_id'] != '') {
            $query = "select * from Contacts" .
                    " where id = " .
                    $response['result']['parent_id'] . ";";

            //urlencode to as its sent over http.
            $queryParam = urlencode($query);

            //creating query string
            $params = "sessionName={$sessionName}" .
                    "&operation=query&query=$queryParam";


            //sending request to vtiger REST Service 
            $rest = new RESTClient();

            $rest->format('json');
            $contact = $rest->get(
                    $vtresturl . "?$params"
            );


            //Objectify the response and check its success
            $contact = json_decode($contact, true);

            if (!$contact['success'])
                throw new Exception($contact['error']['message']);

            //Storing contact name to response
            $response['result']['contactname'] = $contact['result'][0];

            //Building response
            $query = "select accountname from Accounts" .
                    " where id = " .
                    $contact['result'][0]['account_id'] . ";";

            //urlencode to as its sent over http.
            $queryParam = urlencode($query);

            //creating query string
            $params = "sessionName={$sessionName}" .
                    "&operation=query&query=$queryParam";


            //sending request to vtiger REST Service 
            $rest = new RESTClient();

            $rest->format('json');
            $account = $rest->get(
                    $vtresturl . "?$params"
            );

            $account = json_decode($account, true);
            if (!$account['success'])
                throw new Exception($account['error']['message']);
            $response['result']['accountname'] = $account['result'][0]['accountname'];
        }

        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['HelpDesk'];

        unset($response['result']['update_log']);
        unset($response['result']['hours']);
        unset($response['result']['days']);
        unset($response['result']['modifiedtime']);
        unset($response['result']['from_portal']);

        if (is_array($response['result']))
            foreach ($response['result'] as $fieldname => $value) {
                $keyToReplace = array_search($fieldname, $customFields);
                if ($keyToReplace) {
                    unset($response['result'][$fieldname]);
                    $response['result'][$keyToReplace] = $value;
                }
            }
        return $response;
    }

    public function add($sessionName, $vtresturl, $clientid, $session) {


        /**
         * Validations
         */
        $scriptStarted = date("c");
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
                Yii::app()->params[$clientid .
                '_custom_fields']['HelpDesk']
        );

        foreach ($post as $k => $v) {
            $keyToReplace = array_search($k, $customFields);
            if ($keyToReplace) {
                unset($post[$k]);
                $post[$keyToReplace] = $v;
            }
        }
        $session->result->vtigerUserId = '19x' . $session->result->vtigerUserId;
        //get data json 
        $dataJson = json_encode(
                array_merge(
                        $post, array(
            'parent_id' => $session->contactId,
            'assigned_user_id' => $session->result->vtigerUserId,
            'ticketstatus' => (isset($post['ticketstatus']) && !empty($post['ticketstatus'])) ? $post['ticketstatus'] : 'Closed',
                        )
                )
        );
        //print_r($dataJson);die;
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

        //echo $response;die;
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
        /*         * Creating Document* */


        if ($globalresponse->success == false)
            throw new Exception($globalresponse->error->message);

        //Create Documents if any is attached
        $crmid = $globalresponse->result->id;
        $globalresponse->result->documents = Array();
        $globalresponse->result->message = Array();


        $dataJson = array(
            'notes_title' => 'Attachement',
            'assigned_user_id' => $session->result->vtigerUserId,
            'notecontent' => 'Attachement',
            'filelocationtype' => 'I',
            'filedownloadcount' => null,
            'filestatus' => 1,
            'fileversion' => '',
        );


        if (!empty($_FILES) && $globalresponse->success) {

            //Log

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
                            $vtresturl, array(
                        'sessionName' => $sessionName,
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
                                $vtresturl, array(
                            'sessionName' => $sessionName,
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

        $customFields = Yii::app()->params[$clientid .
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
        /*
          if ($post['ticketstatus'] != 'Closed') {
          $email = new AmazonSES();
          //$email->set_region(constant("AmazonSES::" .
          //Yii::app()->params->awsSESRegion));

          if ($globalresponse['result']['drivercauseddamage'] == 'Yes')
          $globalresponse['result']['drivercauseddamage'] == 'Ja';

          if ($globalresponse['result']['drivercauseddamage'] == 'No')
          $globalresponse['result']['drivercauseddamage'] == 'Nej';

          $sesBody = 'Hej ' . $this->_session->contactname .
          ', ' . PHP_EOL .
          PHP_EOL .
          'En skaderapport har skapats.' . PHP_EOL .
          PHP_EOL .
          'Datum och tid: ' . date("Y-m-d H:i") . PHP_EOL .
          'Ticket ID: ' .
          $globalresponse['result']['ticket_no'] . PHP_EOL .
          PHP_EOL .
          '- Besiktningsuppgifter -' . PHP_EOL .
          'Trailer ID: ' .
          $globalresponse['result']['trailerid'] . PHP_EOL .
          'Plats: ' .
          $globalresponse['result']['damagereportlocation'] .
          PHP_EOL .
          'Plomerad: ' . $globalresponse['result']['sealed'] .
          PHP_EOL;

          if ($globalresponse['result']['sealed'] == 'No' ||
          $globalresponse['result']['sealed'] == 'Nej')
          $sesBody .= 'Skivor: ' .
          $globalresponse['result']['plates'] . PHP_EOL .
          'Spännband: ' . $globalresponse['result']['straps'] .
          PHP_EOL;

          $sesBody .= PHP_EOL .
          '- Skadeuppgifter -' . PHP_EOL .
          'skadetyp: ' . $globalresponse['result']['damagetype'] .
          PHP_EOL .
          'Position: ' . $globalresponse['result']['damageposition'] .
          PHP_EOL .
          'Skada orsakad av chaufför: ' .
          $globalresponse['result']['drivercauseddamage'] . PHP_EOL .
          PHP_EOL .
          PHP_EOL .
          '--' .
          PHP_EOL .
          'Gizur Admin';

          if ($clientid == 'clab') {
          $sesResponse = $email->send_email(
          Yii::app()->params->awsSESFromEmailAddress, array(
          'ToAddresses' => array(// Destination (aka To)
          $_SERVER['HTTP_X_USERNAME']
          )
          ), array(// sesMessage (short form)
          'Subject.Data' => date("F j, Y") .
          ': Besiktningsprotokoll för  ' .
          $globalresponse['result']['ticket_no'],
          'Body.Text.Data' => $sesBody
          )
          );
          } else {
          $sesResponse = $email->send_email(
          Yii::app()->params->awsSESFromEmailAddress, array(
          'ToAddresses' => array(// Destination (aka To)
          $_SERVER['HTTP_X_USERNAME']
          )
          ), array(// sesMessage (short form)
          'Subject.Data' => date("F j, Y") .
          ': Besiktningsprotokoll för  ' .
          $globalresponse['result']['ticket_no'],
          'Body.Text.Data' => $sesBody
          )
          );
          }
          }
         * 
         */
    }

}
