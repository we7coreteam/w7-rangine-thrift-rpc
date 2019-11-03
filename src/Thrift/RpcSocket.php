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

namespace W7\ThriftRpc\Thrift;

use Thrift;
use Thrift\Exception\TTransportException;
use Thrift\Transport\TFramedTransport;

class RpcSocket extends TFramedTransport {
	public $buffer = '';
	public $offset = 0;
	public $server;
	protected $fd;
	protected $read_ = true;
	protected $rBuf_ = '';
	protected $wBuf_ = '';

	public function setHandle($fd) {
		$this->fd = $fd;
	}

	public function readFrame() {
		$buf = $this->_read(4);
		$val = unpack('N', $buf);
		$sz = $val[1];

		$this->rBuf_ = $this->_read($sz);
	}

	public function _read($len) {
		if (strlen($this->buffer) - $this->offset < $len) {
			throw new TTransportException('TSocket['.strlen($this->buffer).'] read '.$len.' bytes failed.');
		}
		$data = substr($this->buffer, $this->offset, $len);
		$this->offset += $len;
		return $data;
	}

	public function read($len) {
		if (!$this->read_) {
			return $this->_read($len);
		}

		if (Thrift\Factory\TStringFuncFactory::create()->strlen($this->rBuf_) === 0) {
			$this->readFrame();
		}
		// Just return full buff
		if ($len >= Thrift\Factory\TStringFuncFactory::create()->strlen($this->rBuf_)) {
			$out = $this->rBuf_;
			$this->rBuf_ = null;
			return $out;
		}

		// Return TStringFuncFactory::create()->substr
		$out = Thrift\Factory\TStringFuncFactory::create()->substr($this->rBuf_, 0, $len);
		$this->rBuf_ = Thrift\Factory\TStringFuncFactory::create()->substr($this->rBuf_, $len);
		return $out;
	}

	public function write($buf, $len = null) {
		$this->wBuf_ .= $buf;
	}

	public function flush() {
		$out = pack('N', strlen($this->wBuf_));
		$out .= $this->wBuf_;
		$this->server->send($this->fd, $out);
		$this->wBuf_ = '';
	}
}
