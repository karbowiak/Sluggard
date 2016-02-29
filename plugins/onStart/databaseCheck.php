<?php
use Sluggard\SluggardApp;

class databaseCheck {
    private $config;
    private $discord;
    private $log;

    public function __construct($discord, SluggardApp $app) {
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
    }

    public function onMessage($msgData) {

    }

    public function onStart() {
        $this->log->info("wat");
    }

    public function onTick() {

    }

    public function onTimer() {

    }
}