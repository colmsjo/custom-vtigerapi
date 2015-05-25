<?php

require_once '../lib/aws-php-sdk/sdk.class.php';
$gizurClientId = 'cikab';
$dynamodb = new AmazonDynamoDB();
$region = 'REGION_EU_W1';
$tableName = 'VTIGER_TABDATA';
$dynamodb->set_region(AmazonDynamoDB::REGION_EU_W1);

$response = $dynamodb->get_item(
    array(
        'TableName' => $tableName,
        'Key' => $dynamodb->attributes(
            array('HashKeyElement' => $gizurClientId)
        ),
        'ConsistentRead' => 'true'
    )
);
print_r($response);
/*
$queue = new CFBatchRequest();
$queue->use_credentials($dynamodb->credentials);
// Prepare the data
$post['id'] = array(AmazonDynamoDB::TYPE_STRING => $gizurClientId);
$post['tab_info_array'] = array(
    AmazonDynamoDB::TYPE_STRING => 'constructArray($result_array)'
);
$post['tab_seq_array'] = array(
    AmazonDynamoDB::TYPE_STRING => 'constructArray($seq_array)'
);
$post['tab_ownedby_array'] = array(
    AmazonDynamoDB::TYPE_STRING => 'constructArray($ownedby_array)'
);
$post['action_id_array'] = array(
    AmazonDynamoDB::TYPE_STRING => 'constructSingleStringKeyAndValueArray(
    $actionid_array
    )'
);
$post['action_name_array'] = array(
    AmazonDynamoDB::TYPE_STRING => 'constructSingleStringValueArray(
    $actionname_array
    )'
);
  echo "In create_tab_data_file() $gizurClientId";

$dynamodb->batch($queue)->put_item(
    array(
       'TableName' => $tableName,
       'Item' => $post
       )
);
*/

  $responses = $dynamodb->batch($queue)->send();
 
if ($responses->areOK()) {
    echo "The data has been added to the table." . PHP_EOL;
} else {
    print_r($response);
}
