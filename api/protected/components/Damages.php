<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Damages
 *
 * @author prakash
 */
class Damages extends CApplicationComponent {

    public function Damagelist($sessionName, $vtresturl, $clientid) {
        $flippedCustomFields = array_flip(Yii::app()->params[$clientid .
                '_custom_fields'][$_GET['model']]);

        if (in_array($_GET['fieldname'], $flippedCustomFields)) {
            $fieldname = Yii::app()->params[$clientid .
                    '_custom_fields'][$_GET['model']][$_GET['fieldname']];
        } else {
            $fieldname = $_GET['fieldname'];
        }
        $params = "sessionName={$sessionName}" .
                "&operation=describe" .
                "&elementType=" . $_GET['model'];
        $rest = new RESTClient();
        $rest->format('json');
        $response = $rest->get(
                $vtresturl . "?$params"
        );
        if ($response == '' || $response == null)
            throw new Exception(
            "Blank response received from vtiger: Picklist"
            );

//Objectify the response and check its success
        $response = json_decode($response, true);

        if ($response['success'] == false)
            throw new Exception('Fetching details failed');

        $picklist = '';
        $foundPicklist = false;
        $notPicklist = false;

//Find the appropriate field whose label value needs to
//be sent  
        foreach ($response['result']['fields'] as $field) {

//Check if the field is a picklist
            if ($field['type']['name'] == 'picklist') {

//Loop through all values of the pick list
                foreach ($field['type']['picklistValues']
                as &$option)

//Check if there is a dependency setup
//for the picklist value
                    if (isset($option['dependency'])) {

                        foreach ($option['dependency']
                        as $depFieldname => $dependency) {
                            if (in_array(
                                            $depFieldname, Yii::app()->params[$clientid .
                                            '_custom_fields']['HelpDesk']
                                    )) {
                                $newFieldname = $flippedCustomFields[$depFieldname];
                                $option['dependency'][$newFieldname] = $option['dependency'][$depFieldname];
                                unset($option['dependency'][$depFieldname]);
                            }
                        }
                    }

//Create response to be sent in proper
//format
                $content = json_encode(
                        array(
                            'success' => true,
                            'result' =>
                            $field['type']['picklistValues']
                        )
                );

                if (!isset(
                                $flippedCustomFields[$field['name']]
                        ))
                    $flippedCustomFields[$field['name']] = $field['name'];
                if ($fieldname == $field['name']) {
                    $foundPicklist = true;
                    $picklist = $content;
                }
            } else {

                if ($fieldname == $field['name']) {
                    $notPicklist = true;
                }
            }
        }

        if ($foundPicklist) {

            //Dispatch the response
            return $picklist;
        }

        if ($notPicklist)
            throw new Exception("Not an picklist field");

        if ($notPicklist == false and $foundPicklist == false)
            throw new Exception("Fieldname not found");
    }

    public function UpdateDamageStatus($sessionName, $vtresturl, $clientid) {
        $rest = new RESTClient();

        $rest->format('json');
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
        $retrivedObject['ticketstatus'] = 'Closed';
        $response = $rest->post(
                $vtresturl, array(
            'sessionName' => $sessionName,
            'operation' => 'update',
            'element' => json_encode($retrivedObject)
                )
        );
        $response = json_decode($response, true);

        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['HelpDesk'];


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
                //unset($customFields[$keyToReplace]);                                
            }
        }
        return $response;
    }

    public function UpdateDamageStatusAndNotes($sessionName, $vtresturl, $clientid) {
        $rest = new RESTClient();

        $rest->format('json');
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
        $_PUT = Array();
        parse_str(file_get_contents('php://input'), $_PUT);

$query="Update vtiger_ticketcf set cf_666='".$_PUT['ticketdetails']."', cf_667='".$_PUT['ticketdetails1']."' where ticketid = '".$_PUT['id']."' ";
$connection = Yii::app()->db;
$command = $connection->createCommand($query);
$dataReader = $command->execute();


        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['HelpDesk'];

        $retrivedObject[$customFields['damagestatus']] = $_PUT['damagestatus'];
        $retrivedObject[$customFields['notes']] = $_PUT['notes'];

        $response = $rest->post(
                $vtresturl, array(
            'sessionName' => $sessionName,
            'operation' => 'update',
            'element' => json_encode($retrivedObject)
                )
        );
        $response = json_decode($response, true);

        $customFields = Yii::app()->params[$clientid .
                '_custom_fields']['HelpDesk'];


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
                //unset($customFields[$keyToReplace]);                                
            }
        }
        return $response;
    }

}
