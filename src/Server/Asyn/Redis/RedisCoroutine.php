<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-9-1
 * Time: 下午4:25
 */

namespace Server\Asyn\Redis;

use Server\Coroutine\CoroutineBase;
use Server\Memory\Pool;

class RedisCoroutine extends CoroutineBase
{
    /**
     * @var RedisAsynPool
     */
    public $redisAsynPool;
    public $name;
    public $arguments;
    /**
     * 对象池模式用来代替__construct
     * @param $redisAsynPool
     * @param $name
     * @param $arguments
     * @param $set
     * @return $this
     */
    public function init($redisAsynPool, $name, $arguments, $set)
    {
        $this->redisAsynPool = $redisAsynPool;
        $this->name = $name;
        $this->arguments = $arguments;
        $this->request = "#redis: $name";
        $this->set($set);
        $this->send(function ($result) {
            $this->coPush($result);
        });
        return $this->returnInit();
    }

    public function send($callback)
    {
        $this->token = $this->redisAsynPool->call($this->name, $this->arguments, $callback);
    }

    public function destroy()
    {
        parent::destroy();
        $this->redisAsynPool->removeTokenCallback($this->token);
        $this->token = null;
        $this->redisAsynPool = null;
        $this->name = null;
        $this->arguments = null;
        Pool::getInstance()->push($this);
    }

    protected function onTimerOutHandle()
    {
        parent::onTimerOutHandle();
        $this->redisAsynPool->destoryGarbage($this->token);
    }
}