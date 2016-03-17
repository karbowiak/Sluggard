<?php
namespace Sluggard\Models;

use Sluggard\Lib\Db;
use Sluggard\SluggardApp;

class SluggardData {
    private $db;
    private $app;
    private $config;

    function __construct(SluggardApp $app) {
        $this->app = $app;
        $this->config = $app->config;

        $dbName = $this->config->get("botName", "bot");
        $this->db = new Db($app, "sqlite", $dbName);
    }

    public function query($query, $parameters = array()) {
        return $this->db->query($query, $parameters);
    }

    public function queryField($query, $field, $parameters = array()) {
        return $this->db->queryField($query, $field, $parameters);
    }

    public function queryRow($query, $parameters = array()) {
        return $this->db->queryRow($query, $parameters);
    }

    public function execute($query, $parameters = array(), $returnID = false) {
        return $this->db->execute($query, $parameters, $returnID);
    }
}