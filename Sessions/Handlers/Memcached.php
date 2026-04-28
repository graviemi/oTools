<?php

namespace oTools\Sessions\Handlers;

class Memcached extends HandlerAbstract
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
		\PHPFullcalendar\_::debug('memcached remove : %s %s',$this->prefix ?? 'null',$this->id ?? 'null');
		$this->mc->delete($this->prefix.$this->id);
	}

	public function gc()
	{}
}
