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

namespace W7\ThriftRpc\Server;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\TMultiplexedProcessor;
use W7\App;
use W7\Core\Dispatcher\RequestDispatcher;
use W7\Core\Exception\HandlerExceptions;
use W7\Http\Message\Server\Request;
use W7\Http\Message\Server\Response;
use W7\ThriftRpc\Thrift\RpcSocket;

class Dispatcher extends RequestDispatcher {
	/**
	 * @var TMultiplexedProcessor
	 */
	private $process;

	public function __construct() {
		parent::__construct();
		$this->registerService();
	}

	/**
	 * 注册路由到对应控制器的dispatcher
	 *用户可自定义service，进行数据的处理和返回
	 */
	private function registerService() {
		$loader = new ThriftClassLoader();

		$services = [];
		$dir = APP_PATH . '/ThriftRpc';
		$files = Finder::create()
			->in($dir)
			->files()
			->ignoreDotFiles(true)
			->name('/^[\w\W\d]+Handler.php$/');

		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$name = str_replace('/', '\\', $file->getRelativePath());
			$handler = trim(str_replace('/', '\\', $file->getRelativePathname()), '.php');

			$services[$name] = [
				'handler' => $handler,
				'process' => rtrim($handler, 'Handler') . 'Processor'
			];

			$loader->registerNamespace($name, $dir);
			$loader->registerDefinition($name, $dir);
		}
		$loader->register();

		$this->process = new TMultiplexedProcessor();
		foreach ($services as $key => $value) {
			$serviceHandler = new $value['handler']();
			$serviceProcess = new $value['process']($serviceHandler);
			$this->process->registerProcessor($key, $serviceProcess);
		}
	}

	/**
	 * 解析thrift数据，
	 * @param mixed ...$params
	 * @return \Psr\Http\Message\ResponseInterface|void
	 */
	public function dispatch(...$params) {
		/**
		 * @var Request $psr7Request
		 * @var Response $psr7Response
		 */
		$psr7Request = $params[3];
		$psr7Response = $params[4];
		$contextObj = App::getApp()->getContext();
		$contextObj->setRequest($psr7Request);
		$contextObj->setResponse($psr7Response);

		$socket = new RpcSocket();
		$socket->buffer = $params[2];
		$socket->server = $params[0];
		$socket->setHandle($params[1]);

		try {
			$protocol = new TBinaryProtocol($socket, false, false);
			$this->process->process($protocol, $protocol);
		} catch (\Throwable $throwable) {
			iloader()->get(HandlerExceptions::class)->handle($throwable);
			$socket->server->close($params[1]);
		}
	}
}
