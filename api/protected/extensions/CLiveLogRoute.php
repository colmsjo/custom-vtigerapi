<?php
/**
* LiveLogRoute class file.
*
* @author Anshuk Kumar <anshuk.kumar@essindia.co.in>
* @link http://www.gizur.com/
* @copyright Copyright &copy; 2008-2011 Gizur AB
* @license none
*/

/**
* CLiveLogRoute sends selected log messages to and external server.
*
* The target server addresses may be specified via {@link setServer server} property.
*
* @property string $server The URL to POST the log to.
*
* @author Anshuk Kumar <anshuk.kumar@essindia.co.in>
* @version $Id$
* @package system.logging
*/
class CLiveLogRoute extends CLogRoute
{
    /**
    * @var string server address(url).
    */
    private $_server;
    /**
    * Sends log messages to specified server address.
    * @param array $logs list of log messages
    */
    protected function processLogs($logs)
    {
        $message='';
        foreach($logs as $log)
        $message.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
        $message=wordwrap($message,70);
        $this->postLog($this->getServer(), $message);
    }

    /**
    * Sends the POST to server.
    * @param string $server server url / address
    * @param string $message POST content
    */
    protected function postLog($server, $message)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,            $server );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POST,           1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($message)); 
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/json')); 

        $result=curl_exec ($ch);
    }

    /**
    * @return string server url / address
    */
    public function getServer()
    {
        return $this->_server;
    }

    /**
    * @param string $value server url / address
    */
    public function setServer($value)
    {
        $this->_server=$value;
    }
}
