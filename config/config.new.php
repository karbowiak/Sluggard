<?php
$config["bot"] = array(
    "trigger" => "!",
    "botName" => "EVEBot",
    "userAgent" => ""
);

$config["discord"] = array(
    "email" => "",
    "password" => "",
    "admin" => "", // The owner of the bot
    "adminID" => "", // The discordID of the owner of the bot);
    "token" => "",
);

$config["auth"] = array(
    "authSite" => "http://auth.karbowiak.dk",
    "db" => "/storage/www/auth.karbowiak.dk/config/database/",
    "dbName" => "auth"
);

// A lot of the plugins are disabled, add them here to enable them
$config["enabledplugins"] = array(
    "about",
    "eveCharInfo",
    "eveCorpInfo",
    "eveFitting",
    "eveItem",
    "evePrice",
    "eveStatus",
    "eveTime",
    "help",
    "user",
    "wolframAlpha",
    "eveMails",
    "eveNotifications",
    "memoryReclamation",
    "pluginTick",
    "tqStatus",
    "updateDatabase",
    "eightball",
    "coinflip",
    "guilds",
    "join",
    "meme"
);

$config["twitter"] = array(
    "consumerKey" => "",
    "consumerSecret" => "",
    "accessToken" => "",
    "accessTokenSecret" => ""
);

// Should probably load all of these from SeAT?
$config["eve"] = array(
    "apiKeys" => array(
        "karbowiak" => array(
            "keyID" => ,
            "vCode" => "",
            "characterID" =>
        )
    )
);

$config["wolframalpha"] = array(
    "appID" => ""
);

$config["filereader"] = array(
    "channelconfig" => array(
        "pings" => array(
            "default" => true,
            "searchString" => false,
            "textStringPrepend" => "@everyone |",
            "textStringAppend" => "",
            "channelID" => 119136919346085888
        ),
        "intel" => array(
            "default" => false,
            "searchString" => "intel",
            "textStringPrepend" => "",
            "textStringAppend" => "",
            "channelID" => 149918425018400768
        ),
        "blackops" => array(
            "default" => false,
            "searchString" => "blops",
            "textStringPrepend" => "@everyone |",
            "textStringAppend" => "",
            "channelID" => 149925578135306240
        )
    ),
    "db" => "/tmp/discord.db"
);

$config["evemails"] = array(
    "fromIDs" => array(98047305, 99005805),
    "channelID" => 120639051261804544
);

$config["periodictqstatus"] = array(
    "channelID" => 118441700157816838
);

$config["notifications"] = array(
    "channelID" => 149918425018400768
);

$config["twitteroutput"] = array(
    "channelID" => 120474010109607937
);