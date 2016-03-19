<?php

namespace Sluggard\Lib;


use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Sluggard\SluggardApp;

class composeMsgData
{
    /**
     * @var SluggardApp
     */
    private $app;
    /**
     * @var \Sluggard\Models\SluggardData
     */
    private $db;

    /**
     * Storage constructor.
     * @param SluggardApp $app
     */
    function __construct(SluggardApp &$app) {
        $this->app = $app;
        $this->db = $app->sluggarddata;
    }

    public function data(Message $msgData, Discord $botData) {
        $channelData = Channel::find($msgData["channel_id"]);

        if ($channelData->is_private == true)
            $channelData->setAttribute("name", $msgData->author->username);


        $msgData = (object)array(
            "isBotOwner" => false,
            "user" => $msgData,
            "message" => (object)array(
                "lastSeen" => $this->db->queryField("SELECT lastSeen FROM usersSeen WHERE id = :id", "lastSeen", array(":id" => $msgData->author->id)),
                "lastSpoke" => $this->db->queryField("SELECT lastSpoke FROM usersSeen WHERE id = :id", "lastSpoke", array(":id" => $msgData->author->id)),
                "timestamp" => $msgData->timestamp->toDateTimeString(),
                "id" => $msgData->author->id,
                "message" => $msgData->content,
                "channelID" => $msgData->channel_id,
                "from" => $msgData->author->username,
                "fromID" => $msgData->author->id,
                "fromDiscriminator" => $msgData->author->discriminator,
                "fromAvatar" => $msgData->author->avatar
            ),
            "channel" => $channelData,
            "guild" => $channelData->is_private ? (object)array("name" => "private conversation") : Guild::find($channelData->guild_id),
            "botData" => $botData
        );

        return $msgData;
    }
}