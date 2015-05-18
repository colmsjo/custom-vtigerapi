<?php

class ValidateRequest extends CApplicationComponent {
    /*
     * List of valid models
     */

    private $_validModels = Array(
        'Users',
        'Contacts',
        'Products',
        'Accounts',
        'User',
        'HelpDesk',
        'Contacts',
        'Assets',
        'About',
        'DocumentAttachments',
        'DocumentAttachment',
        'Authenticate',
        'Cron',
        'Batches', // Batch Integration
        'Background', // Background Status
        'Images', // S3 Images
        'ExistingDamages'
    );

    /**
     * Authenticates a request.	 
     */
    public function authenticate() {
        //First we validate the model
        if (!isset($_GET['model']))
            throw new Exception('Model not present');

        if (in_array($_GET['model'], $this->_validModels) === false)
            throw new Exception('Model not supported');

        if (!isset($_SERVER['HTTP_X_USERNAME']))
            throw new Exception('Could not find enough credentials');

        //Check if the password is provided in the request
        if (!isset($_SERVER['HTTP_X_PASSWORD']))
            throw new Exception('Could not find enough credentials');


        if (empty($_SERVER['HTTP_X_USERNAME']))
            throw new Exception("Credentials are invalid.", 2004);

        if (empty($_SERVER['HTTP_X_PASSWORD']))
            throw new Exception("Credentials are invalid.", 2004);


        //Check Acceptable language of request
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
            if (!is_null($_SERVER['HTTP_ACCEPT_LANGUAGE']))
                if ($_SERVER['HTTP_ACCEPT_LANGUAGE'] != 'null')
                    if (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'en') === false)
                        throw new Exception('Language not supported');

        //Check Acceptable mime-type of request    
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            if (!is_null($_SERVER['HTTP_ACCEPT_LANGUAGE']))
                if ($_SERVER['HTTP_ACCEPT_LANGUAGE'] != 'null')
                    if (strpos($_SERVER['HTTP_ACCEPT'], 'json') === false) {
                        if (!(
                                strpos($_SERVER['HTTP_ACCEPT'], 'html') !== false && $_GET['model'] == 'About'
                                ))
                            throw new Exception(
                            'Mime-Type not supported', 1005
                            );
                    }
        }

        if (!isset($_SERVER['HTTP_X_CLIENTID']))
            throw new Exception('Client ID not found');
    }

}
