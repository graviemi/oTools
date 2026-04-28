<?php

namespace oTools\Sessions\Handlers;

abstract class HandlerAbstract implements HandlerInterface 
{
	protected $id = null;

	public function open(string $id)
	{
		$this->id = $id;
	}

	public function load()
	{}

	public function save(array &$data)
	{}

	public function touch()
	{}

	public function remove()
	{}

	public function gc()
	{}
}
