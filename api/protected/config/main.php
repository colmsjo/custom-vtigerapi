<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');
// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Gizur API',
    'defaultController' => 'api',
    // preloading 'log' component
    'preload' => array('log'),
    // autoloading model and component classes
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.extensions.*',
    ),
    'modules' => array(
    // uncomment the following to enable the Gii tool
    /*
      'gii'=>array(
      'class'=>'system.gii.GiiModule',
      'password'=>'Enter Your Password Here',
      // If removed, Gii defaults to localhost only. Edit carefully to taste.
      'ipFilters'=>array('127.0.0.1','::1'),
      ),
     */
    ),
    // application components
    'components' => array(
        'user' => array(
            // enable cookie-based authentication
            'allowAutoLogin' => true,
        ),
        // uncomment the following to enable URLs in path-format
        'urlManager' => array(
            'urlFormat' => 'path',
            'rules' => array(
                array('api/login', 'pattern' => '/<model:(Authenticate)>/<action:(login|logout)>', 'verb' => 'POST'),
                array('api/Assets', 'pattern' => '/<model:(Assets)>', 'verb' => 'GET'),
                array('api/DamageStatus', 'pattern' => '/<model:(HelpDesk)>/<fieldname:\w+>', 'verb' => 'GET'),
                array('api/DamageUpdateStatus', 'pattern' => '/<model:(HelpDesk)>/<id:[0-9x]+>', 'verb' => 'PUT'),
                array('api/DamageUpdateStatusAndNotes', 'pattern' => '/<model:(HelpDesk)>/<action:(updatedamagenotes)>/<id:[0-9x]+>', 'verb' => 'PUT'),
                array('api/UpdatePassword', 'pattern' => '/<model:(Authenticate)>/<action:(reset|changepw)>', 'verb' => 'PUT'),
            ),
        ),
        'cache' => array(
            'class' => 'CMemCache',
            'servers' => array(
                array(
                    'host' => '172.17.0.2',
                    'port' => 11211,
                    'weight' => 100,
                ),
            ),
        ),
        // database settings are configured in database.php
        'db' => require(dirname(__FILE__) . '/database.php'),
        'errorHandler' => array(
            // use 'site/error' action to display errors
            'errorAction' => 'api/error',
        ),
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
            // uncomment the following to show log messages on web pages
            /*
              array(
              'class'=>'CWebLogRoute',
              ),
             */
            ),
        ),
    ),
    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array(
        // this is used in contact page
        'adminEmail' => 'webmaster@example.com',
        'vtRestUrl' => 'https://clabvtigerdev.gizur.com/vtigercrm/webservice.php',
        'clab_custom_fields' => Array(
            'HelpDesk' => Array(
                'tickettype' => 'cf_649',
                'trailerid' => 'cf_640',
                'damagereportlocation' => 'cf_661',
                'sealed' => 'cf_651',
                'plates' => 'cf_662',
                'straps' => 'cf_663',
                'reportdamage' => 'cf_654',
                'damagetype' => 'cf_659',
                'damageposition' => 'cf_658',
                'drivercauseddamage' => 'cf_657',
                'notes' => 'cf_664',
                'damagestatus' => 'cf_665'
            ),
            'Assets' => Array(
                'trailertype' => 'cf_660'
            )
        )
    ),
);
