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

namespace W7\ThriftRpc\Session\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use W7\Core\Middleware\MiddlewareAbstract;
use W7\ThriftRpc\Collector\CollectorManager;
use W7\ThriftRpc\Session\SessionCollector;

class SessionMiddleware extends MiddlewareAbstract {
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$fd = icontext()->getContextDataByKey('fd');
		$request->session = iloader()->get(CollectorManager::class)->getCollector(SessionCollector::getName())->get($fd);
		$request->session->set('time', time());
		$request->session->gc();

		return $handler->handle($request);
	}
}
