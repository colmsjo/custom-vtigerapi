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
        if (isset($_GET['category'])) {

            $query = "SELECT ticket.ticketid
id,ticket.ticket_no,ticketcf.cf_640 trailerid,ticketcf.cf_661
damagereportlocation,ticketcf.cf_665 damagestatus,
ticketcf.cf_654 reportdamage,ticketcf.cf_659 damagetype,ticketcf.cf_658
damageposition,ticketcf.cf_657 drivercauseddamage,concat(con.firstname,'
',con.lastname) contactname
,account.accountname,entity.createdtime,entity.modifiedtime
FROM vtiger_troubletickets AS ticket
LEFT JOIN vtiger_ticketcf AS ticketcf ON ( ticket.ticketid =
ticketcf.ticketid )
LEFT JOIN vtiger_contactdetails AS con ON ( ticket.parent_id = contactid )
LEFT JOIN vtiger_account AS account ON ( con.accountid = account.accountid )
LEFT JOIN vtiger_crmentity as entity on (entity.crmid=ticket.ticketid) ";
            $whereClause = Array();
            if ($_GET['category'] == 'inoperation') {
                $whereClause[] = "ticket.status = 'Closed'";
            }
            if ($_GET['category'] == 'damaged') {
                $whereClause[] = "ticket.status = 'Open'";
            }

            if (isset($_GET['reportdamage']))
                if ($_GET['reportdamage'] != 'all') {
                    $whereClause[] = "ticketcf.cf_659'" . ucwords($_GET['reportdamage']) . "'";
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
                    $whereClause[] = "ticketcf.cf_640='" . $_GET['trailerid'] . "'";
            }

//Attaching where clause to filter
            if (count($whereClause) != 0)
                $query = $query . " where " .
                        implode(" and ", $whereClause);

//Terminating the query

            if (isset($_GET['minLimit']) && isset($_GET['maxLimit'])) {
                $minLimit = $_GET['minLimit'];
                $maxLimit = $_GET['maxLimit'];
                $query = $query . " LIMIT $minLimit, $maxLimit ;";
            } else {
                $query = $query . ";";
            }

            $connection = Yii::app()->db;
            $command = $connection->createCommand($query);
            $dataReader = $command->query(); // execute a query SQL
            $response['success'] = 1;
            $result = $dataReader->readAll();
            $response['result'] = $result;

            if ($response['success'] == false)
                throw new Exception('Fetching details failed');

            /**
             * Fetch the documents
             */
            foreach ($response['result'] as $key => $ticket) {
                $sqldoc = "select concat('17x',note.notesid) as id, note.filename as filename from
vtiger_notes as note where note.notesid in (select notesid from
vtiger_senotesrel where crmid=" . $ticket['id'] . ")";
                $command1 = $connection->createCommand($sqldoc);
                $dataReader1 = $command1->query();
                $documents = $dataReader1->readAll();
                foreach ($documents as $k => $doc) {
                    $response['result'][$key]['files'][]['path'] = Yii::app()->params['awsS3BucketUrl'] . '/' . $doc['filename'];
                }
            }
            return $response;
        }
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

    public function add($parentId) {
//----------------------------
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
//-------------------------------------
//c

        $query = "UPDATE vtiger_crmentity_seq SET id = id +1";
        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $dataReader = $command->execute(); // execute a query SQL

        if (!$dataReader) {
            throw new Exception(
            "Some Error in Database Connection", 1002
            );
        }
        $query = "SELECT id FROM vtiger_crmentity_seq";
        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $dataReader = $command->query(); // execute a query SQL
        $crmid = $dataReader->read();
        $crmid = $crmid['id'];
//------------------------------------------------------------
        $query = "INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid, modifiedby, setype, description, createdtime, modifiedtime, viewedtime, status, version, presence, deleted) 
VALUES ($crmid,'1001','1001','1001','HelpDesk',NULL,CURDATE(),CURDATE(),CURDATE(),NULL,'0','1','0')";

        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $result = $command->execute(); // execute a query SQL
        if (!$result)
            throw new Exception(
            "Some Error in Database Connection", 1002
            );
        $query = "SELECT MAX(ticket_no) ticket_no FROM vtiger_troubletickets";
        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $dataReader = $command->query(); // execute a query SQL
        $ticketno = $dataReader->read();
        $ticketno = $ticketno['ticket_no'];
        $ticketno++;
        $pid = explode('x', $parentId);
        $parentId = $pid[1];
        $query = "INSERT INTO `vtiger_troubletickets`(`ticketid`, `ticket_no`, `groupname`, `parent_id`, `product_id`, `priority`, `severity`, `status`, `category`, `title`, `solution`, `update_log`, `version_id`, `hours`, `days`, `from_portal`) 
VALUES ($crmid,'$ticketno',NULL,'$parentId',0,'','','Open','','" . $_POST['ticket_title'] . "','','',NULL,0,0,0)";
        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $result = $command->execute(); // execute a query SQL
        if (!$result) {
            throw new Exception(
            "Some Error in Database Connection", 1002
            );
        }
        $query1 = "INSERT INTO `vtiger_ticketcf`(`ticketid`, `cf_640`, `cf_649`, `cf_651`, `cf_654`, `cf_657`, `cf_658`, `cf_659`, `cf_661`, `cf_662`, `cf_663`, `cf_664`, `cf_665`) "
                . "VALUES ($crmid,'" . $_POST['trailerid'] . "','','" . $_POST['sealed'] . "','" . $_POST['reportdamage'] . "','" . $_POST['drivercauseddamage'] . "','" . $_POST['damageposition'] . "','" . $_POST['damagetype'] . "','" . $_POST['damagereportlocation'] . "','','','','')";
        $connection = Yii::app()->db;
        $command1 = $connection->createCommand($query1);
        $result1 = $command1->execute(); // execute a query SQL
        if (!$result1) {
            throw new Exception(
            "Some Error in Database Connection", 1002
            );
        }
	
	$_FILES = array(
            'test' => array(
                'name' => 'test.jpg',
                'type' => 'image/jpg',
                'size' => 542,
                'tmp_name' => '/apps/custom-vtigerapi/test/images/image-to-upload.jpg',
                'error' => 0
            )
        );
        if (!empty($_FILES)) {
	    $notesid = $crmid;

            foreach ($_FILES as $key => $file) {
                $uniqueid = uniqid();

//Upload file to Amazon S3
                $sThree = new AmazonS3();
                $sThree->set_region(
                        constant("AmazonS3::" . Yii::app()->params->awsS3Region)
                );
		
		
                $response = $sThree->create_object(
                        Yii::app()->params->awsS3Bucket, $crmid . '_' . $uniqueid . '_' . $file['name'], 
			array(
                    		'fileUpload' => $file['tmp_name'],
                  	        'contentType' => $file['type'],
                   		 'headers' => array(
                       		 'Cache-Control' => 'max-age',
                    	         'Content-Language' => 'en-US',
                        	 'Expires' =>'Thu, 01 Dec 1994 16:00:00 GMT',
                    		)
                        )
                );
	
                if ($response->isOK()) {
		    $crmid=$this->savecrmenties();
			//echo $crmid;
                    $notesid = $notesid + 1;
		    $querydocument = " INSERT INTO `vtiger_notes`(`notesid`, `note_no`, `title`, `filename`, `notecontent`, `folderid`, `filetype`, `filelocationtype`, `filedownloadcount`, `filestatus`, `filesize`, `fileversion`) VALUES "
                            . "($notesid,'','Attachement','" . $file['name'] . "','Attachement','1','" . $file['type'] . "','0','I',NULL,'" . $file['size'] . "','')";
	           // echo $querydocument;
		    $con = Yii::app()->db;
                    $com = $con->createCommand($querydocument);
                    $result = $com->execute();

                    $query2 = "INSERT INTO `vtiger_senotesrel`(`crmid`, `notesid`) VALUES ($crmid,$notesid)";
              
		    $con = Yii::app()->db;
                    $com = $con->createCommand($query2);
                    $res = $com->execute();
              }
            }
        }
        $response = new stdClass();
        $response->success = true;
        $response->message = "Ticket Created Sucessfully.";
        return $response;
    }

    function savecrmenties()
    {
        $query = "UPDATE vtiger_crmentity_seq SET id = id +1";
        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $dataReader = $command->execute(); // execute a query SQL

        if (!$dataReader) {
            throw new Exception(
            "Some Error in Database Connection", 1002
            );
        }
        $query = "SELECT id FROM vtiger_crmentity_seq";
        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $dataReader = $command->query(); // execute a query SQL
        $crmid = $dataReader->read();
        $crmid = $crmid['id'];
//------------------------------------------------------------
        $query = "INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid, modifiedby, setype, description, createdtime, modifiedtime, viewedtime, status, version, presence, deleted) 
VALUES ($crmid,'1001','1001','1001','Documents',NULL,CURDATE(),CURDATE(),CURDATE(),NULL,'0','1','0')";

        $connection = Yii::app()->db;
        $command = $connection->createCommand($query);
        $result = $command->execute(); // execute a query SQL
        if (!$result)
            throw new Exception(
            "Some Error in Database Connection", 1002
            );
        return $crmid;
    }

    public function ExistingDamages() {

        if (isset($_GET['category'])) {

            $query = "SELECT ticket.ticketid
id,ticket.ticket_no,ticketcf.cf_640 trailerid,ticketcf.cf_661
damagereportlocation,ticketcf.cf_665 damagestatus,
ticketcf.cf_654 reportdamage,ticketcf.cf_659 damagetype,ticketcf.cf_658
damageposition,ticketcf.cf_657 drivercauseddamage,concat(con.firstname,'
',con.lastname) contactname
,account.accountname,entity.createdtime,entity.modifiedtime
FROM vtiger_troubletickets AS ticket
LEFT JOIN vtiger_ticketcf AS ticketcf ON ( ticket.ticketid =
ticketcf.ticketid )
LEFT JOIN vtiger_contactdetails AS con ON ( ticket.parent_id = contactid )
LEFT JOIN vtiger_account AS account ON ( con.accountid = account.accountid )
LEFT JOIN vtiger_crmentity as entity on (entity.crmid=ticket.ticketid) ";
            $whereClause = Array();
            if ($_GET['category'] == 'inoperation') {
                $whereClause[] = "ticket.status = 'Closed'";
            }
            if ($_GET['category'] == 'damaged') {
                $whereClause[] = "ticket.status = 'Open'";
            }

            if (isset($_GET['reportdamage']))
                if ($_GET['reportdamage'] != 'all') {
                    $whereClause[] = "ticketcf.cf_659'" . ucwords($_GET['reportdamage']) . "'";
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
                    $whereClause[] = "ticketcf.cf_640='" . $_GET['trailerid'] . "'";
            }

//Attaching where clause to filter
            if (count($whereClause) != 0)
                $query = $query . " where " .
                        implode(" and ", $whereClause);

//Terminating the query

            if (isset($_GET['minLimit']) && isset($_GET['maxLimit'])) {
                $minLimit = $_GET['minLimit'];
                $maxLimit = $_GET['maxLimit'];
                $query = $query . " LIMIT $minLimit, $maxLimit ;";
            } else {
                $query = $query . ";";
            }

            $connection = Yii::app()->db;
            $command = $connection->createCommand($query);
            $dataReader = $command->query(); // execute a query SQL
            $response['success'] = 1;
            $result = $dataReader->readAll();
            $response['result'] = $result;

            if ($response['success'] == false)
                throw new Exception('Fetching details failed');

            /**
             * Fetch the documents
             */
            foreach ($response['result'] as $key => $ticket) {
                $sqldoc = "select concat('17x',note.notesid) as id, note.filename as filename from
vtiger_notes as note where note.notesid in (select notesid from
vtiger_senotesrel where crmid=" . $ticket['id'] . ")";
                $command1 = $connection->createCommand($sqldoc);
                $dataReader1 = $command1->query();
                $documents = $dataReader1->readAll();
                foreach ($documents as $k => $doc) {
                    $response['result'][$key]['files'][]['path'] = Yii::app()->params['awsS3BucketUrl'] . '/' . $doc['filename'];
                }
            }
            return $response;
        }
    }
}

