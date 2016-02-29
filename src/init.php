<?php
/**
 * @property
 */
use Discord\Discord;
use Discord\WebSockets\WebSocket;
use Sluggard\SluggardApp;

/** @var SluggardApp $app */
$app = new SluggardApp();

// Define where the lib/model files are
$load = array(
    BASEDIR . "/src/Lib/*.php",
    BASEDIR . "/src/Model/*.php"
);

// Load the lib/model files
foreach($load as $path) {
    $files = glob($path);

    foreach($files as $file) {
        $exp = explode("src/", $file);
        $baseName = basename($file);
        $callName = str_replace(".php", "", $baseName);
        $namespace = "\\Sluggard\\" . str_replace(".php", "", str_replace("/", "\\", $exp[1]));

        // Load all the models and Libraries as singletons in Slim..
        $app->singleton(strtolower($callName), function ($container) use ($app, $namespace) {
            return new $namespace($app);
        });
    }
}

// Startup discord and the websocket instance
$discord = new Discord($app->config->get("email", "discord"), $app->config->get("password", "discord"));
$websocket = new WebSocket($discord);

// Load in all the plugins
$pluginDirs = array(BASEDIR . "/plugins/onMessage/*.php", BASEDIR . "/plugins/onStart/*.php", BASEDIR . "/plugins/onTick/*.php", BASEDIR . "/plugins/onTimer/*.php");
$plugins = array();
foreach($pluginDirs as $pluginDir) {
    foreach(glob($pluginDir) as $plug) {
        $baseName = str_replace(".php", "", basename($plug));
        if(!in_array($baseName, $app->config->getAll("enabledPlugins")))
            continue;

        require_once($plug);
        $app->log->notice("Loading: " . str_replace(".php", "", basename($plug)));
        $fileName = str_replace(".php", "", basename($plug));

        /** @var SluggardApp $app */
        $plugin = new $fileName($discord, $app);
        $plugins[] = $plugin;
    }
}