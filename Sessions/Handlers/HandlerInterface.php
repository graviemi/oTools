<?php

namespace oTools\Sessions\Handlers;

interface HandlerInterface
{
	public function open(string $id);
	public function load();
	public function save(array &$data);
	public function touch();
	public function remove();
	public function gc();
}
