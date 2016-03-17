<?php

use Sluggard\SluggardApp;

/**
 * Class fileReader
 */
class checkCharacter {
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
     * fileReader constructor.
     * @param $discord
     * @param SluggardApp $app
     */
    public function __construct($discord, SluggardApp &$app) {
        $this->app = $app;
        $this->config = $app->config;
        $this->discord = $discord;
        $this->log = $app->log;
        $this->sluggardDB = $app->sluggarddata;
        $this->ccpDB = $app->ccpdata;
        $this->authData = $app->authdata;
        $this->curl = $app->curl;
        $this->storage = $app->storage;
        $this->trigger = $app->triggercommand;
    }

    /**
     * When a message arrives that contains a trigger, this is started
     *
     * @param $msgData
     */
    public function onMessage($msgData) {

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
        // Fetch all the characterIDs from the database
        $users = $this->sluggardDB->query("SELECT * FROM authentications");

        foreach($users as $user) {
            $discordID = $user["discordID"];
            $guildID = $user["guildID"];
            $characterID = $user["characterID"];
            $corporationID = $user["corporationID"];
            $allianceID = $user["allianceID"];

            // Get the information for this user from CCP
            $ccpData = json_decode(json_encode(new SimpleXMLElement($this->curl->getData("https://api.eveonline.com/eve/CharacterAffiliation.xml.aspx?ids={$characterID}"))), true);
            $data = $ccpData["result"]["rowset"]["row"]["@attributes"];
            $currentCharacterID = $data["characterID"];
            $currentCorporationID = $data["corporationID"];
            $currentAllianceID = $data["allianceID"];

            // Lets just be sure we're doing this for the correct character.. CCP is weird sometimes
            if($currentCharacterID == $characterID) {
                $remove = false;

                // Remove if the guy switched corp
                if($currentCorporationID != $corporationID)
                    $remove = true;

                // Remove if the guy switched alliance
                if($currentAllianceID != $allianceID)
                    $remove = true;

                // Lets remove the groups from this user (Every single role!)
                if($remove == true) {
                    $guild = $this->discord->guilds->get("id", $guildID);
                    $guildName = $guild->name;
                    $member = $guild->members->get("id", $discordID);
                    $memberName = $member->user->username;
                    $roles = $member->roles;

                    // Remove all roles, we don't care what roles they are, remove them all..
                    // Can't remove server owner tho, so.. mehe..
                    foreach($roles as $role) {
                        $member->removeRole($role);
                    }

                    // Delete the auth info from the db
                    $this->sluggardDB->execute("DELETE FROM authentication WHERE discordID = :discordID", array(":discordID" => $discordID));

                    $this->log->info("Deleted all roles for {$memberName} on {$guildName}");
                }
            }
        }
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
            "name" => "",
            "trigger" => array(""),
            "information" => "",
            "timerFrequency" => 3600 // Every hour
        );
    }
}