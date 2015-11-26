<?php

require_once(__DIR__ . "/vendor/autoload.php");
use Discord\Discord;

$dirs = array(
    __DIR__ . "/config/*.php",
    __DIR__ . "/library/*.php",
);

foreach($dirs as $dir)
{
    $files = glob($dir);
    foreach($files as $file)
        if(file_exists($file))
            require_once($file);
}

class DiscordBot
{
    var $config;
    var $discord;
    var $oldMsgID = array();
    var $oldMsg = array();
    var $channelIDs = array();
    var $plugins = array();

    var $guilds;

    function __construct($config)
    {
        $this->config = $config;
        $this->discord = $discord = new Discord($this->config["discord"]["email"], $this->config["discord"]["password"]);
        $this->guilds = $config["discord"]["guilds"];
        foreach($this->guilds as $guildID) {
            $guildData = $this->discord->api("guild")->channels()->show($guildID);
            foreach($guildData as $gData)
                $this->channelIDs[] = $gData["id"];
        }
        $this->loadPlugins();
        $this->main($config);
    }

    function loadPlugins()
    {
        $this->plugins = array();
        $files = glob(__DIR__ . "/plugins/*.php");
        foreach($files as $file)
        {
            require_once($file);
            $fileName = str_replace(".php", "", basename($file));
            $this->plugins[] = new $fileName();
        }

        foreach($this->plugins as $plugin)
            $plugin->init($this->config, $this->discord);
    }

    function main()
    {
        while(true)
        {
            try {
                foreach($this->channelIDs as $key => $channel)
                    $messages[$key] = $this->discord->api("channel")->messages()->show($channel, 1, null, isset($this->oldMsgID[$key]) ? $this->oldMsgID[$key] : null);

                foreach($messages as $key => $subArray)
                {
                    foreach($subArray as $message) {
                        $this->oldMsgID[$key] = $message["id"];

                        // The message is new, so lets do stuff to it.. time to call up all the plugins!
                        if (!isset($this->oldMsg[$key]) || ($this->oldMsg[$key] != $message["content"])) {
                            $channelID = $message["channel_id"];
                            $msg = $message["content"];

                            // Bit of a long thing that should be in it's own plugin really.. but whatever!
                            if(stringStartsWith($msg, "!help"))
                            {
                                $command = explode(" ", $msg);
                                unset($command[0]);
                                $command = implode($command);
                                $showAll = true;
                                foreach($this->plugins as $plugin)
                                {
                                    if($command == $plugin->information()["name"]) {
                                        $this->discord->api("channel")->messages()->create($channelID, $plugin->information()["information"]);
                                        $showAll = false;
                                    }
                                }

                                if($showAll == true)
                                {
                                    $commands = array();
                                    foreach($this->plugins as $plugin)
                                    {
                                        $info = $plugin->information();
                                        if(!empty($info["name"]))
                                            $commands[] = $info["name"];
                                    }

                                    $this->discord->api("channel")->messages()->create($channelID, "**Error:** No specific plugin requested, here is a list of plugins available: **" . implode(" */*  ", $commands) . "**");
                                }
                            }

                            foreach ($this->plugins as $plugin)
                                $plugin->onMessage($msg, $channelID);

                            $this->oldMsg[$key] = $message["content"];
                        }

                        // Tick
                        foreach($this->plugins as $plugin)
                            $plugin->tick();
                    }
                }
            } catch (Exception $e)
            {
                var_dump($e->getMessage());
            }
            // sleep for 500ms between every message fetch
            usleep(500000);
        }
    }

}

$bot = new DiscordBot($config);