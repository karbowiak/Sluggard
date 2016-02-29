<?php
namespace Sluggard\Lib;
use Sluggard\SluggardApp;

/**
 * Class config
 * @package Sluggard\Lib
 */
class config {
    /**
     * @var SluggardApp
     */
    private $app;

    /**
     * config constructor.
     * @param SluggardApp $app
     */
    public function __construct(SluggardApp $app) {
        $this->app = $app;
    }

    /**
     * @param $key
     * @param null $type
     * @param null $default
     * @return null
     */
    public function get($key, $type = null, $default = null) {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if(!empty($config[$type][$key]))
            return $config[$type][$key];

        return $default;
    }

    /**
     * @param null $type
     * @return array
     */
    public function getAll($type = null) {
        $config = array();
        include(BASEDIR . "/config/config.php");

        $type = strtolower($type);
        if(!empty($config[$type]))
            return $config[$type];

        return array();
    }
}