<?php

namespace BotMan\Drivers\WeChat;

use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class WeChatAudioDriver extends WeChatDriver
{
    const DRIVER_NAME = 'WeChatAudio';

    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return self::DRIVER_NAME;
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('MsgType')) && $this->event->get('MsgType') === 'voice';
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new IncomingMessage(Audio::PATTERN, $this->event->get('ToUserName'), $this->event->get('FromUserName'),
            $this->event);
        $message->setAudio($this->getAudio());

        return [$message];
    }

    /**
     * Retrieve audio url from an incoming message.
     * @return array
     */
    private function getAudio()
    {
        $audioUrl = 'http://file.api.wechat.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$this->event->get('MediaId');

        return [new Audio($audioUrl, $this->event)];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
