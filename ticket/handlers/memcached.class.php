<?php

namespace oTools\ticket\handlers;

class memcached implements handler
{

	protected $mc;
	protected int $ttl;
	protected string $prefix;

	public function __construct(string $address, int $port, int $ttl, string $prefix)
	{
		$this->mc = new \Memcached();
		$this->mc->addServer($address,$port);
		$this->ttl = $ttl;
		$this->prefix = $prefix;
	}

	public function put(string $key, array $data) : void
	{
		$this->mc->set($this->prefix.$key,serialize($data),$this->ttl);
	}

	public function get(string $key) : array
	{
		if (($data = $this->mc->get($this->prefix.$key)) === false)
			throw new exception('undefined key');
		$this->mc->delete($this->prefix.$key);
		return unserialize($data);
	}
}
