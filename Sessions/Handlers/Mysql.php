<?php

namespace oTools\Sessions\Handlers;

use oTools\mysql\base;

class Mysql extends HandlerAbstract
{
	protected $base;
	protected $table;
	protected $ttl;

	public function __construct(base $base, string $table, int $ttl)
	{
		$this->base = $base;
		$this->table = $table;
		$this->ttl = $ttl;
	}

	public function load()
	{
		if (is_null($this->id))
			return [];
		$result = $this->base->request()
			->sql(sprintf('SELECT /data FROM /%s WHERE /id = :id',$this->table))
			->sbinds($this->id)
			->exec();
		if (count($result) !== 1)
			return [];
		return unserialize($result[0]['data']);
	}

	public function save(array &$data)
	{
		$this->base->request()
			->sql(sprintf('INSERT INTO /%s (/id, /data, /time) VALUES (:id, :dt, :tm) ON DUPLICATE KEY UPDATE /data = :dt',$this->table))
			->sbinds($this->id,serialize($data),time())
			->exec();
	}

	public function touch()
	{
		$this->base->request()
			->sql(sprintf('UPDATE /%s SET /time = :tm WHERE /id = :id',$this->table))
			->sbinds(time(),$this->id)
			->exec();
	}

	public function remove()
	{
		$this->base->request()
			->sql(sprintf('DELETE FROM /%s WHERE /id = :id',$this->table))
			->sbinds($this->id)
			->exec();
	}

	public function gc()
	{
		$this->base->request()
			->sql(sprintf('DELETE FROM /%s WHERE /time < :tm',$this->table))
			->sbinds(time() - $this->ttl)
			->exec();
	}
}
