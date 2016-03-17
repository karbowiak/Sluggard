<?php
namespace Sluggard\Models;

use Sluggard\Lib\Db;
use Sluggard\SluggardApp;

class CCPData {
    private $db;
    private $app;

    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->db = new Db($app, "sqlite", "ccpData");
    }

    public function query(string $query, array $parameters = array()) {
        return $this->db->query($query, $parameters);
    }

    public function queryField(string $query, string $field, array $parameters = array()) {
        return $this->db->queryField($query, $field, $parameters);
    }

    public function queryRow(string $query, array $parameters = array()) {
        return $this->db->queryRow($query, $parameters);
    }

    public function execute(string $query, array $parameters = array(), bool $returnID = false) {
        return $this->db->execute($query, $parameters, $returnID);
    }
}