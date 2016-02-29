<?php
namespace Sluggard\Lib;
use Sluggard\SluggardApp;

/**
 * Class triggerCommand
 * @package Sluggard\Lib
 */
class triggerCommand {
    /**
     * @var SluggardApp
     */
    private $app;

    /**
     * triggerCommand constructor.
     * @param SluggardApp $app
     */
    public function __construct(SluggardApp $app) {
        $this->app = $app;
    }

    /**
     * @param $message
     * @param $commandTrigger
     * @return array
     */
    public function trigger($message, $commandTrigger) {
        if(empty($commandTrigger))
            return null;

        $commandTrigger = is_array($commandTrigger) ? $commandTrigger : array($commandTrigger);

        foreach($commandTrigger as $trigger) {
            if(substr($message, 0, strlen($trigger)) == $trigger) {
                $data = explode(" ", $message);
                $trigger = str_replace($this->app["config"]->get("trigger", "bot", "!"), "", $data[0]);
                unset($data[0]);

                $data = array_values($data);
                $messageString = implode(" ", $data);

                return array("trigger" => $trigger, "messageArray" => $data, "messageString" => $messageString);
            }
        }
        return null;
    }

    /**
     * @param $message
     * @param $commandTrigger
     * @return bool
     */
    public function containsTrigger($message, $commandTrigger) {
        $commandTrigger = is_array($commandTrigger) ? $commandTrigger : array($commandTrigger);

        foreach($commandTrigger as $trigger) {
            if(substr($message, 0, strlen($trigger)) == $trigger)
                return true;
        }
        return false;
    }
}