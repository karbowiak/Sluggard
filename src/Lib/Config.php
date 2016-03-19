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
    public function __construct(SluggardApp &$app) {
        $this->app = $app;
    }

    /**
     * @param string $key
     * @param string|null $type
     * @param string|null $default
     * @return string|array
     */
    public function get($key, $type = null, $default = null) {
        $config = array();
        include(BASEDIR . "/config/config." . BOTNAME . ".php");

        $type = strtolower($type);
        if(!empty($config[$type][$key]))
            return $config[$type][$key];

        return $default;
    }

    /**
     * @param string|null $type
     * @return array
     */
    public function getAll($type = null) {
        $config = array();
        include(BASEDIR . "/config/config." . BOTNAME . ".php");

        $type = strtolower($type);
        if(!empty($config[$type]))
            return $config[$type];

        return array();
    }
}