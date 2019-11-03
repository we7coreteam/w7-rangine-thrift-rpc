<?php

/**
 * This file is part of Rangine
 *
 * (c) We7Team 2019 <https://www.rangine.com/>
 *
 * document http://s.w7.cc/index.php?c=wiki&do=view&id=317&list=2284
 *
 * visited https://www.rangine.com/ for more details
 */

namespace W7\ThriftRpc\Server;

use Swoole\Server as RpcServer;
use W7\Core\Server\ServerAbstract;
use W7\Core\Server\SwooleEvent;

class Server extends ServerAbstract {
	public function getType() {
		return 'thrift-rpc';
	}

	public function start() {
		$this->server = $this->getServer();
		$this->server->set($this->setting);

		//执行一些公共操作，注册事件等
		$this->registerService();

		ievent(SwooleEvent::ON_USER_BEFORE_START, [$this->server]);

		$this->server->start();
	}

	public function getServer() {
		if (empty($this->server)) {
			$this->server = new RpcServer($this->setting['host'], $this->setting['port'], $this->setting['mode'], $this->setting['sock_type']);
		}
		return $this->server;
	}

	/**
	 * @var \Swoole\Server $server
	 * 通过侦听端口的方法创建服务
	 */
	public function listener(\Swoole\Server $server) {
		$tcpServer = $server->addListener($this->setting['host'], $this->setting['port'], $this->setting['sock_type']);
		//tcp需要强制关闭其它协议支持，否则继续父服务
		$tcpServer->set([
			'open_http2_protocol' => false,
			'open_http_protocol' => false,
			'open_websocket_protocol' => false,
		]);
		$event = (new SwooleEvent())->getDefaultEvent()[$this->getType()];
		foreach ($event as $eventName => $class) {
			if (empty($class)) {
				continue;
			}
			$object = \iloader()->get($class);
			$tcpServer->on($eventName, [$object, 'run']);
		}
	}

	protected function getDefaultSetting(): array {
		$setting = parent::getDefaultSetting();
		$setting['dispatch_mode'] = 2;

		return $setting;
	}
}
