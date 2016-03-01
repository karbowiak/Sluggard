<?php
/**
 * @property
 */
use Discord\Discord;
use Discord\WebSockets\WebSocket;
use Sluggard\SluggardApp;

/** @var SluggardApp $app */
$app = new SluggardApp();

// Put startTime into app
$app->startTime = $startTime;

// Define where the lib/model files are
$load = array(
    BASEDIR . "/src/Lib/*.php",
    BASEDIR . "/src/Models/*.php"
);

// Load the lib/model files
foreach($load as $path) {
    $files = glob($path);

    foreach($files as $file) {
        $exp = explode("src/", $file);
        $baseName = basename($file);
        $callName = str_replace(".php", "", $baseName);
        $namespace = "\\Sluggard\\" . str_replace(".php", "", str_replace("/", "\\", $exp[1]));

        // If skipAutoLoad exists as a method, we skip loading this library (Should only really be the Db)
        if(method_exists(new $namespace($app), "skipAutoLoad"))
            continue;

        // Load all the models and Libraries as singletons in Slim..
        $app->singleton(strtolower($callName), function ($container) use ($app, $namespace) {
            return new $namespace($app);
        });
    }
}

// First launch?
if(!file_exists(BASEDIR . "/config/database/"))
{
    // Check all the directories exist (cache, config, config/database)
    $directories = array(BASEDIR . "/cache/", BASEDIR . "/config/", BASEDIR . "/config/database");

    foreach($directories as $directory) {
        if(!file_exists($directory)) {
            $app->log->info("Creating {$directory}");
            mkdir($directory);
            chmod($directory, 0755);
        }
    }

    $app->sluggarddatabaseupdater->createSluggardDB();
    $app->ccpdatabaseupdater->createCCPDB();
}

// Startup discord and the websocket instance
$discord = new Discord($app->config->get("email", "discord"), $app->config->get("password", "discord"));
$websocket = new WebSocket($discord);

$pluginDirs = array("onMessage", "onStart", "onTick", "onTimer");
$plugins = array();
foreach($pluginDirs as $pluginDir) {
    $files = glob(PLUGINDIR . $pluginDir . "/*.php");
    $plugins[$pluginDir] = array();

    foreach($files as $plug) {
        $baseName = str_replace(".php", "", basename($plug));
        if(!in_array($baseName, $app->config->getAll("enabledPlugins")))
            continue;

        require_once($plug);
        $app->log->notice("Loading: " . str_replace(".php", "", basename($plug)));
        $fileName = str_replace(".php", "", basename($plug));

        /** @var SluggardApp $app */
        $plugin = new $fileName($discord, $app);
        $plugins[$pluginDir][] = $plugin;
    }
}