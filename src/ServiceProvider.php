<?php

/**
 * Rangine thrift rpc server
 *
 * (c) We7Team 2019 <https://www.rangine.com>
 *
 * document http://s.w7.cc/index.php?c=wiki&do=view&id=317&list=2284
 *
 * visited https://www.rangine.com for more details
 */

namespace W7\ThriftRpc;

use W7\Core\Log\LogManager;
use W7\Core\Provider\ProviderAbstract;
use W7\Core\Server\ServerEnum;
use W7\Core\Server\SwooleEvent;
use W7\ThriftRpc\Listener\CloseListener;
use W7\ThriftRpc\Listener\ConnectListener;
use W7\ThriftRpc\Listener\ReceiveListener;
use W7\ThriftRpc\Server\Server;

class ServiceProvider extends ProviderAbstract {
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->addOpenBaseDir();
		$this->registerLog();
		$this->registerCommand();

		$this->registerServer('thrift-rpc', Server::class);
		/**
		 * @var SwooleEvent $event
		 */
		$event = iloader()->get(SwooleEvent::class);
		$events = $event->getDefaultEvent()[ServerEnum::TYPE_TCP];
		$events[SwooleEvent::ON_RECEIVE] = ReceiveListener::class;
		$events[SwooleEvent::ON_CONNECT] = ConnectListener::class;
		$events[SwooleEvent::ON_CLOSE] = CloseListener::class;
		$this->registerServerEvent('thrift-rpc', $events);
	}

	private function addOpenBaseDir() {
		if (!is_dir(BASE_PATH . '/thrift')) {
			mkdir(BASE_PATH . '/thrift');
		}
		$this->registerOpenBaseDir([
			BASE_PATH . '/thrift',
			BASE_PATH . '/gen-php'
		]);
	}

	private function registerLog() {
		if (!empty($this->config->getUserConfig('log')['channel']['thrift-rpc'])) {
			return false;
		}
		/**
		 * @var LogManager $logManager
		 */
		$logManager = iloader()->get(LogManager::class);
		$logManager->addChannel('thrift-rpc', 'stream', [
			'path' => RUNTIME_PATH . '/logs/thrift-rpc.log',
			'level' => ienv('LOG_CHANNEL_THRIFT_RPC_LEVEL', 'debug'),
		]);
	}
}
