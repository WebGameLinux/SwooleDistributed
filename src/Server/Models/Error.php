<?php

/**
 * 钉钉的model，用于通信管理
 * Created by tmtbe on 16-6-20.
 * Class DingDing
 * @property SocketCurl $SocketCurl
 */

namespace Server\Models;
use Server\Asyn\HttpClient\HttpClientPool;
use Server\CoreBase\ChildProxy;
use Server\CoreBase\Model;

class Error extends Model
{
    private $robot = '';
    /**
     * @var HttpClientPool
     */
    private $client;

    private $redis_prefix;
    private $redis_timeOut;
    private $url;

    public function __construct($proxy = ChildProxy::class)
    {
        parent::__construct($proxy);
        $this->robot = $this->config->get('error.dingding_robot');
        $this->client = get_instance()->getAsynPool('dingdingRest');
        $this->redis_timeOut = $this->config->get('error.redis_timeOut', 36000);
        $this->redis_prefix = $this->config->get('error.redis_prefix', "@sd-error");
        $this->url = $this->config->get('error.url');
    }

    public function push($data)
    {
        $id = session_create_id();
        $key = $this->redis_prefix . $id;
        $this->redis_pool->getCoroutine()->set($key, $data, ["NX", "EX" => $this->redis_timeOut]);
        $url = $this->url . "?id=" . $id;
        secho("Error", "访问：$url 查看");
        $this->sendLinkMessage("发生异常:$key", $url);
    }

    /**
     * @param $title
     * @param string $link
     * @return \Server\Asyn\HttpClient\HttpClientRequestCoroutine
     */
    public function sendLinkMessage($title, $link = '')
    {
        $json = json_encode([
            'msgtype' => 'link',
            'link' => [
                'title' => $title,
                "messageUrl" => $link,
                "text" => "点击查看"
            ]
        ]);
        $result = $this->client->httpClient->setData($json)
            ->setHeaders(['Content-type' => 'application/json'])->setMethod('POST')->coroutineExecute($this->robot);
        return $result;
    }
    /**
     * @param $title
     * @param string $text
     * @return \Generator
     */
    public function sendMarkDownMessage($title, $text = '')
    {
        $json = json_encode([
            'msgtype' => 'markdown',
            'markdown'=> [
                'title' => $title,
                "text" => $text
            ]
        ]);
        $result = $this->client->httpClient->setData($json)
            ->setHeaders(['Content-type'=>'application/json'])->setMethod('POST')->coroutineExecute($this->robot);
        return $result;
    }
}