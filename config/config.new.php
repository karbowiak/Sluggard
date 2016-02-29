<?php
$config["bot"] = array(
    "trigger" => "!",
    "botName" => "EVEBot"
);

$config["discord"] = array(
    "email" => "",
    "password" => "",
    "admin" => "", // The owner of the bot
    "adminID" => "" // The discordID of the owner of the bot);
);

$config["enabledplugins"] = array(
    "about",
    "eveCharInfo",
    "eveCorpInfo",
    "eveItem",
    "evePrice",
    "eveStatus",
    "eveTime",
    "help",
    "user",
    "wolframAlpha",
    "databaseCheck",
    "eveMails",
    "eveNotifications",
    "fileReader",
    "twitterNotifications",
    "memoryReclamation",
    "pluginTick",
    "tqStatus",
    "updateDatabase"
);

$config["wolframalpha"] = array(
    "appID" => ""
);