<?php

class configuration
{

    public function get()
    {
        return array(
            'url' => 'http://localhost/api/',
            'GIZURCLOUD_API_KEY' => 'GZCLD51A309109FD3551A309109FE26',
            'GIZURCLOUD_SECRET_KEY' => "51a309109fca42.1554128151a309109fcb20.84498075",
            'API_VERSION' => '0.1',
            'credentials' => array(
                'niclas.andersson@coop.se' => "nicand",
                //'mobile_user@gizur.com' => 'ivry34aq',
            ),
            'clientid' => 'clab'
        );
    }

    public function getBikerPortalCredentials()
    {
        return array(
            'url' => 'http://gizur-env.elasticbeanstalk.com/applications/cikab',
            'credentials' => array(
                array('user_name' => 'gizur-ess-prabhat@gizur.com',
                    'user_password' => 'essindia', 'id' => 2),
            ),
            'version' => '5.4.0'
        );
    }

}
