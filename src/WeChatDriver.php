<?php

namespace BotMan\Drivers\WeChat;

use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Users\User;
use BotMan\Drivers\WeChat\Exceptions\UnsupportedAttachmentException;
use BotMan\Drivers\WeChat\Exceptions\WeChatException;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class WeChatDriver extends HttpDriver implements VerifiesService
{
    const DRIVER_NAME = 'WeChat';

	/**
	 * @param Request $request
	 * @throws WeChatException
	 */
    public function buildPayload(Request $request)
    {
        try {
            $xml = @simplexml_load_string($request->getContent(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $data = json_decode($json, true);
        } catch (\Exception $e) {
            throw new WeChatException('Unable to parse the incoming request', 0, $e);
        }
        $this->payload = $request->request->all();
        $this->event = Collection::make($data);
        $this->config = Collection::make($this->config->get('wechat'));
	    Response::create()->send();
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('MsgType')) && ! is_null($this->event->get('MsgId')) && ($this->event->get('MsgType') === 'text' || $this->event->get('MsgType') === 'link');
    }

    /**
     * @param  IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

	/**
	 * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
	 * @return User
	 * @throws WeChatException
	 */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $response = $this->http->post('https://api.wechat.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$matchingMessage->getRecipient().'&lang=en_US',
            [], [], [], true);
        $responseData = json_decode($response->getContent());

        if (isset($responseData->errcode) && $responseData->errcode != 0) {
        	throw new WeChatException('Error retrieving user info: '. $responseData->errmsg);
        }

        $nickname = isset($responseData->nickname) ? $responseData->nickname : '';

        return new User($matchingMessage->getSender(), null, null, $nickname);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
    	if ($this->event->get('MsgType') === 'text') {
    		$text = $this->event->get('Content');
	    } else {
    		$text = $this->event->get('Url');
	    }

        return [
            new IncomingMessage($text, $this->event->get('FromUserName'),
                $this->event->get('ToUserName'), $this->event),
        ];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getAccessToken()
    {
        $response = $this->http->post('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid='.$this->config->get('app_id').'&secret='.$this->config->get('app_key'),
            [], []);
        $responseData = json_decode($response->getContent());

        return $responseData->access_token;
    }

	/**
	 * @param string|\BotMan\BotMan\Messages\Outgoing\Question|IncomingMessage $message
	 * @param IncomingMessage $matchingMessage
	 * @param array $additionalParameters
	 * @return Response
	 * @throws UnsupportedAttachmentException
	 */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'touser' => $matchingMessage->getSender(),
            'msgtype' => 'text',
        ], $additionalParameters);

        if ($message instanceof Question) {
            $parameters['text'] = [
                'content' => $message->getText(),
            ];
        } elseif ($message instanceof OutgoingMessage) {
            $attachment = $message->getAttachment();

            if ($attachment !== null && ! $attachment instanceof Image) {
            	throw new UnsupportedAttachmentException('The '.get_class($attachment).' is not supported (currently: Image)');
            }

	        $parameters['msgtype'] = 'news';
            if ($attachment !== null) {
                $article = [
                    'title' => $message->getText(),
                    'picurl' => $attachment->getUrl(),
                ];
            } else {
                $article = [
                    'title' => $message->getText(),
                    'picurl' => null,
                ];
            }

            $parameters['news'] = [
                'articles' => [$article],
            ];
        } else {
            $parameters['text'] = [
                'content' => $message,
            ];
        }

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        return $this->http->post('https://api.wechat.com/cgi-bin/message/custom/send?access_token='.$this->getAccessToken(),
            [], $payload, [], true);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->config->get('app_id')) && ! is_null($this->config->get('app_key'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        return $this->http->post('https://api.wechat.com/cgi-bin/'.$endpoint.'?access_token='.$this->getAccessToken(),
            [], $parameters, [], true);
    }

    /**
     * @param Request $request
     * @return null|Response
     */
    public function verifyRequest(Request $request)
    {
        if ($request->get('signature') !== null && $request->get('timestamp') !== null && $request->get('nonce') !== null && $request->get('echostr') !== null) {
            $tmpArr = [$this->config->get('verification'), $request->get('timestamp'), $request->get('nonce')];
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);

            if ($tmpStr == $request->get('signature')) {
                return Response::create($request->get('echostr'))->send();
            }
        }
    }
}
