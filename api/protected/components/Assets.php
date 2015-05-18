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

    public function assetslist($sessionName, $vtresturl, $clientid) {
        $query = "select * from " . $_GET['model'] . ";";
        $queryParam = urlencode($query);
        $params = "sessionName={$sessionName}" .
                "&operation=query&query=$queryParam";
        $rest = new RESTClient();


        //echo $vtresturl . "?$params";die;
        $rest->format('json');
        $response = $rest->get(
                $vtresturl . "?$params"
        );
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

}
