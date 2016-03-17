<?php

namespace Sluggard\Lib;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Sluggard\SluggardApp;

/**
 * Class Log
 * @package Sluggard\Lib
 */
class log {
    /**
     * @var Logger
     */
    private $log;
    /**
     * @var SluggardApp
     */
    private $app;

    /**
     * log constructor.
     * @param SluggardApp $app
     */
    public function __construct(SluggardApp &$app)
    {
        $this->app = $app;
        $this->log = new Logger("Sluggard");
        $this->log->pushHandler(new StreamHandler("php://output", Logger::INFO));
    }

    /**
     * Prints out information to the log
     *
     * @param string $logMessage
     * @param array $logData
     */
    public function info(string $logMessage, array $logData = array()) {
        $this->log->addInfo($logMessage, $logData);
    }

    /**
     * Prints out debug to the log
     *
     * @param $logMessage
     * @param $logData
     */
    public function debug(string $logMessage, array $logData = array()) {
        $this->log->addDebug($logMessage, $logData);
    }

    /**
     * Prints out warning to the log
     *
     * @param $logMessage
     * @param $logData
     */
    public function warn(string $logMessage, array $logData = array()) {
        $this->log->addWarning($logMessage, $logData);
    }

    /**
     * Prints out error to the log
     *
     * @param $logMessage
     * @param $logData
     */
    public function err(string $logMessage, array $logData = array()) {
        $this->log->addError($logMessage, $logData);
    }

    /**
     * Prints out notice to the log
     *
     * @param $logMessage
     * @param $logData
     */
    public function notice(string $logMessage, array $logData = array()) {
        $this->log->addNotice($logMessage, $logData);
    }
}