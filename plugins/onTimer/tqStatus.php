<?php

use Sluggard\SluggardApp;

class tqStatus {
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
    }

    public function onTick() {
    }

    public function onTimer() {
    }
}