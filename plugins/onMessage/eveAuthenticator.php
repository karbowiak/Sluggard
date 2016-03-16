<?php

use Sluggard\SluggardApp;

/**
 * Class about
 */
class eveAuthenticator {
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var \Sluggard\Lib\config
     */
    private $config;
    /**
     * @var \Discord\Discord
     */
    private $discord;
    /**
     * @var \Sluggard\Lib\log
     */
    private $log;
    /**
     * @var \Sluggard\Models\SluggardData
     */
    private $sluggardDB;
    /**
     * @var \Sluggard\Models\CCPData
     */
    private $ccpDB;
    /**
     * @var \Sluggard\Lib\cURL
     */
    private $curl;
    /**
     * @var \Sluggard\Lib\Storage
     */
    private $storage;
    /**
     * @var \Sluggard\Lib\triggerCommand
     */
    private $trigger;
    /**
     * @var \Sluggard\Models\AuthData
     */
    private $authData;

    /**
     * about constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp $app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;
        $this->authData = $app->authdata;
    }

    /**
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     */
    public function onMessage($msgData) {
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if(isset($data["trigger"])) {
            $channelName = $msgData->channel->name;
            $guildName = $msgData->guild->name;
            $authString = trim($data["messageString"]);
            $private = (bool) $msgData->channel->is_private;

            $authData = $this->authData->queryRow("SELECT * FROM registrations WHERE authString = :authString AND active = 1", array(":authString" => $authString));

            if($private) {
                return $msgData->user->reply("**Error:** you are trying to send your auth token in private. This won't work because i need Guild information. Post it in a channel on the server where you want to be authed");
            }

            // Someone had a valid auth string, amazing
            if(!empty($authData)) {
                $groups = json_decode($authData["groups"], true);
                $roles = $msgData->guild->roles;
                $guild = $this->discord->guilds->get("id", $msgData->guild->id);
                $member = $guild->members->get("id", $msgData->user->author->id);
                $username = $msgData->user->author->username;
                $discordID = $msgData->user->author->id;
                $characterID = $authData["characterID"];
                $corporationID = $authData["corporationID"];
                $allianceID = $authData["allianceID"];

                foreach($roles as $role) {
                    $roleName = $role->name;

                    if(in_array($roleName, $groups)) {
                        // Add user to group
                        $member->addRole($role);
                        $member->save();
                    }
                }

                // Add the user data to the local database so we can recheck it
                $this->sluggardDB->execute("REPLACE INTO authentications (discordID, characterID, corporationID, allianceID) VALUES (:discordID, :characterID, :corporationID, :allianceID)", array("discordID" => $discordID, ":characterID" => $characterID, ":corporationID" => $corporationID, ":allianceID" => $allianceID));

                // Now set the auth to inactive, and we'll be golden
                $this->authData->execute("UPDATE registrations SET active = 0 WHERE authString = :authString", array(":authString" => $authString));
                $this->log->info("Authenticating {$username} in {$channelName} on {$guildName}");
                $msgData->user->reply("You have now been added to the following groups: " . implode(", ", $groups));
            } else {
                $msgData->user->reply("**Error:** you are trying to use an already used auth code, or a non-existing auth code. Either way, prepare to get #rekt");
            }

            //var_dump($this->authData->query("SELECT * FROM registrations"));
           //$this->log->info("Sending about info to {$channelName} on {$guildName}");
           //$msgData->user->reply($msg);
        }
    }

    /**
     * When the bot starts, this is started
     */
    public function onStart() {

    }

    /**
     * When the bot does a tick (every second), this is started
     */
    public function onTick() {

    }

    /**
     * When the bot's tick hits a specified time, this is started
     *
     * Runtime is defined in $this->information(), timerFrequency
     */
    public function onTimer() {

    }

    /**
     * @return array
     *
     * name: is the name of the script
     * trigger: is an array of triggers that can trigger this plugin
     * information: is a short description of the plugin
     * timerFrequency: if this were an onTimer script, it would execute every x seconds, as defined by timerFrequency
     */
    public function information() {
        return array(
            "name" => "auth",
            "trigger" => array("!auth"),
            "information" => "Authenticates you up against the auth site located at " . $this->config->get("authSite", "auth"),
            "timerFrequency" => 0
        );
    }
}