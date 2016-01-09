<?php

class auth
{
    /**
     * @var
     */
    var $config;
    /**
     * @var
     */
    var $discord;
    /**
     * @var
     */
    var $logger;

    /**
     * @param $config
     * @param $discord
     * @param $logger
     */
    function init($config, $discord, $logger)
    {
        $this->config = $config;
        $this->discord = $discord;
        $this->logger = $logger;
    }

    /**
     *
     */
    function tick()
    {
    }

    /**
     * @param $msgData
     */
    function onMessage($msgData)
    {
        $message = $msgData["message"]["message"];
        $channelName = $msgData["channel"]["name"];
        $guildName = $msgData["guild"]["name"];
        $channelID = $msgData["message"]["channelID"];
        $userID = @$msgData["channel"]["recipient"]["id"];
        $isPrivate = $msgData["channel"]["is_private"];

        $data = command($message, $this->information()["trigger"]);
        if (isset($data["trigger"]) && $isPrivate) {
            $trigger = $data["trigger"];
            $messageArray = $data["messageArray"];
            $messageString = $data["messageString"];
            $guildID = dbQueryField("SELECT serverID FROM discordUsers WHERE authString = :authString", "serverID", array(":authString" => $messageString));

            if(!$guildID)
                return $this->discord->api("channel")->messages()->create($channelID, "**Error:** There is no user with that authentication string on this server.");

            $guildData = $this->discord->api("guild")->show($guildID);
            $userData = dbQueryRow("SELECT * FROM discordUsers WHERE authString = :authString", array(":authString" => $messageString));
            dbExecute("UPDATE discordUsers SET discordID = :discordID WHERE authString = :authString", array(":discordID" => $userID, ":authString" => $messageString));
            $corporationName = dbQueryField("SELECT corporationName FROM corporations WHERE corporationID = :corporationID", "corporationName", array(":corporationID" => $userData["corporationID"]));
            $allianceName = $userData["allianceID"] ? dbQueryField("SELECT allianceName FROM alliances WHERE allianceID = :allianceID", "allianceName", array(":allianceID" => $userData["allianceID"])) : null;

            // Is it the first guy registered on the entire server?
            $addToAdmin = false;
            $count = dbQueryField("SELECT count(*) as cnt FROM discordUsers WHERE serverID = :serverID", "cnt", array(":serverID" => $guildID));
            if($count <= 1)
                $addToAdmin = true;

            foreach($guildData["roles"] as $role)
            {
                $roleID = $role["id"];
                if($role["name"] == $corporationName || $role["name"] == $allianceName)
                    $this->discord->api("guild")->members()->promote($guildID, $userID, array($roleID));

                if($role["name"] == "Admin" && $addToAdmin == true)
                    $this->discord->api("guild")->members()->promote($guildID, $userID, array($roleID));
            }
            $this->discord->api("channel")->messages()->create($channelID, "**Success:** You have now been granted basic roles. To get more roles, talk to your CEO / Directors");
        }
    }

    /**
     * @return array
     */
    function information()
    {
        return array(
            "name" => "auth",
            "trigger" => array("!auth"),
            "information" => "Authenticates a person again Rena's Discord manager"
        );
    }

        /**
         * @param $msgData
         */
        function onMessageAdmin($msgData)
        {
        }

}
