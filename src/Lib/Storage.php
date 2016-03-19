<?php

namespace Sluggard\Lib;


use Sluggard\SluggardApp;

/**
 * Class Storage
 * @package Sluggard\Lib
 */
class Storage
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var \Sluggard\Models\SluggardData
     */
    private $db;

    /**
     * Storage constructor.
     * @param SluggardApp $app
     */
    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->db = $app->sluggarddata;
    }

    /**
     * Set a permanent storage object into the database
     *
     * @param $key
     * @param $value
     * @return null
     */
    public function set(string $key, string $value) {
        $this->db->execute("REPLACE INTO storage (`key`, value) VALUES (:key, :value)", array(":key" => $key, ":value" => $value));
    }

    /**
     * Get a permanent storage object from the database
     *
     * @param $key
     * @return null|string
     */
    public function get(string $key) {
        return $this->db->queryField("SELECT value FROM storage WHERE `key` = :key COLLATE NOCASE", "value", array(":key" => $key));
    }
}