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

namespace W7\ThriftRpc\Listener;

use W7\Core\Listener\ListenerAbstract;
use W7\Http\Message\Server\Request;
use W7\ThriftRpc\Collector\CollectorManager;

class ConnectListener extends ListenerAbstract {
	public function run(...$params) {
		iloader()->get(CollectorManager::class)->set($params[1], new Request('POST', '/'));
	}
}
