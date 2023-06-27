<?php

namespace oTools\session\handlers;

class memcached extends handler
{
	protected $ttl;
	protected $mc;
	protected $prefix;

	public function __construct(\Memcached $mc,int $ttl,string $prefix)
	{
		$this->mc = $mc;
		$this->ttl = $ttl;
		$this->prefix = $prefix;
	}

	public function load()
	{
		if (is_null($this->id))
			return [];
		if (($data = $this->mc->get($this->prefix.$this->id)) === false)
			return [];
		return $data;
	}

	public function save(array &$data)
	{
		$this->mc->set($this->prefix.$this->id,$data,$this->ttl);
	}

	public function touch()
	{
		$this->mc->touch($this->prefix.$this->id,$this->ttl);
	}

	public function remove()
	{
		$this->mc->delete($this->prefix.$this->id);
	}

	public function gc()
	{}
}
