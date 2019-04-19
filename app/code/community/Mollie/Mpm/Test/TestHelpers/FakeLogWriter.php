<?php
class Mollie_Mpm_Test_TestHelpers_FakeLogWriter extends Zend_Log_Writer_Stream
{
    /**
     * @var array
     */
    static private $messages = [];

    public function write($event)
    {
        if (!empty($event['message'])) {
            static::$messages[] = $event['message'];
        }
    }

    static public function getMessages()
    {
        return static::$messages;
    }
}