<?php
$config["bot"] = array(
    "trigger" => "!",
    "botName" => "EVEBot",
    "userAgent" => "Discord Bot belonging to karbowiak@gmail.com"
);

$config["discord"] = array(
    "email" => "bot@karbowiak.dk",
    "password" => "29641363kK",
    "admin" => "Karbowiak", // The owner of the bot
    "adminID" => "118440839776174081", // The discordID of the owner of the bot);
    "token" => ""
);

$config["auth"] = array(
    "authSite" => "http://auth.karbowiak.dk",
    "db" => "/storage/www/auth.karbowiak.dk/config/database/",
    "dbName" => "auth"
);

$config["enabledplugins"] = array(
    "about",
    "eveAuthenticator",
    "eveCharInfo",
    "eveCorpInfo",
    "eveFitting",
    "eveItem",
    "evePrice",
    "eveStatus",
    "eveTime",
    "help",
    "logFileGenerator",
    "user",
    "wolframAlpha",
    "eveMails",
    "eveNotifications",
    "fileReader",
    "twitterNotifications",
    "memoryReclamation",
    "pluginTick",
    "tqStatus",
    "updateDatabase",
    "checkCharacter"
);

$config["twitter"] = array(
    "consumerKey" => "aYWw583bWVp9XuKErqMhGBJNV",
    "consumerSecret" => "UYxlRTSxAfGiMfwYOsu1cXcnc9b5lTMJeilOECJMHAbyZljQTD",
    "accessToken" => "375268066-esznMGK7qbbhEDyZZljjSKRaNfvkmsPzkj5bGJR4",
    "accessTokenSecret" => "E2NnGeP4MDGRmpdVDB6DZYy6VtRrW3uHsULmQbktLdqTN"
);

// Should probably load all of these from SeAT?
$config["eve"] = array(
    "apiKeys" => array(
        "karbowiak" => array(
            "keyID" => 4852220,
            "vCode" => "HabsFVp7Be5xWll39CQEwPnva22kgfz4luJFZulDN2ugdaJY5yqwpsIxECWIIkyP",
            "characterID" => 268946627
        )
    )
);

$config["wolframalpha"] = array(
    "appID" => "ERP78A-WJ3WX6LALY"
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