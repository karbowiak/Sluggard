<?php

use Sluggard\SluggardApp;

class meme {
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
        $message = $msgData->message->message;
        $data = $this->trigger->trigger($message, $this->information()["trigger"]);

        if (isset($data["trigger"])) {
            $memes = array(
                'dank meme',
                '>mfw no gf',
                "m'lady *tip*",
                'le toucan has arrived',
                "jet juel can't melt dank memes",
                '༼ つ ◕_◕ ༽つ gibe',
                'ヽ༼ຈل͜ຈ༽ﾉ raise your dongers ヽ༼ຈل͜ຈ༽ﾉ',
                'ヽʕ •ᴥ•ʔﾉ raise your koalas ヽʕ﻿ •ᴥ•ʔﾉ',
                'ಠ_ಠ',
                '(－‸ლ)',
                '( ͡° ͜ʖ ͡°)',
                '( ° ͜ʖ͡°)╭∩╮',
                '(╯°□°）╯︵ ┻━┻',
                '┬──┬ ノ( ゜-゜ノ)',
                '•_•) ( •_•)>⌐■-■ (⌐■_■)',
                "i dunno lol ¯\\(°_o)/¯",
                "how do i shot web ¯\\(°_o)/¯",
                '(◕‿◕✿)',
                'ヾ(〃^∇^)ﾉ',
                '＼(￣▽￣)／',
                '(ﾉ◕ヮ◕)ﾉ*:･ﾟ✧',
                'ᕕ( ͡° ͜ʖ ͡°)ᕗ',
                'ᕕ( ᐛ )ᕗ ᕕ( ᐛ )ᕗ ᕕ( ᐛ )ᕗ',
                "(ﾉ◕ヮ◕)ﾉ *:･ﾟ✧ SO KAWAII ✧･:* \\(◕ヮ◕\\)",
                'ᕙ༼ຈل͜ຈ༽ᕗ. ʜᴀʀᴅᴇʀ,﻿ ʙᴇᴛᴛᴇʀ, ғᴀsᴛᴇʀ, ᴅᴏɴɢᴇʀ .ᕙ༼ຈل͜ຈ༽ᕗ',
                "(∩ ͡° ͜ʖ ͡°)⊃━☆ﾟ. * ･ ｡ﾟyou've been touched by the donger fairy",
                '(ง ͠° ͟ل͜ ͡°)ง ᴍᴀsᴛᴇʀ ʏᴏᴜʀ ᴅᴏɴɢᴇʀ, ᴍᴀsᴛᴇʀ ᴛʜᴇ ᴇɴᴇᴍʏ (ง ͠° ͟ل͜ ͡°)ง',
                "(⌐■_■)=/̵͇̿̿/'̿'̿̿̿ ̿ ̿̿ ヽ༼ຈل͜ຈ༽ﾉ keep your dongers where i can see them",
                '[̲̅$̲̅(̲̅ ͡° ͜ʖ ͡°̲̅)̲̅$̲̅] do you have change for a donger bill [̲̅$̲̅(̲̅ ͡° ͜ʖ ͡°̲̅)̲̅$̲̅]',
                '╰( ͡° ͜ʖ ͡° )つ──☆*:・ﾟ clickty clack clickty clack with this chant I summon spam to the chat',
                'work it ᕙ༼ຈل͜ຈ༽ᕗ harder make it (ง •̀_•́)ง better do it ᕦ༼ຈل͜ຈ༽ᕤ faster raise ur ヽ༼ຈل͜ຈ༽ﾉ donger',
            );

            $msgData->user->reply($memes[array_rand($memes)]);
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
            "name" => "meme",
            "trigger" => array("!meme"),
            "information" => "Returns a dank meme..",
            "timerFrequency" => 0
        );
    }
}