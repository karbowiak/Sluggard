<?php
namespace Sluggard\Models;

use Sluggard\Lib\Db;
use Sluggard\SluggardApp;

class SluggardData {
    private $db;
    private $app;
    private $config;

    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->config = $app->config;

        $dbName = $this->config->get("botName", "bot");
        $this->db = new Db($app, "sqlite", $dbName);
    }

    public function query(string $query, array $parameters = array()): array {
        return $this->db->query($query, $parameters);
    }

    public function queryField(string $query, string $field, array $parameters = array()): string {
        return $this->db->queryField($query, $field, $parameters);
    }

    public function queryRow(string $query, array $parameters = array()): array {
        return $this->db->queryRow($query, $parameters);
    }

    public function execute(string $query, array $parameters = array(), bool $returnID = false): int {
        return $this->db->execute($query, $parameters, $returnID);
    }
}