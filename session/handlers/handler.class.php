<?php

namespace oTools\session\handlers;

abstract class handler
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
