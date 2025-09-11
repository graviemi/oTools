<?php

namespace oTools\ticket\handlers;

interface handler
{
	public function put(string $key, array $data) : void;
	public function get(string $key) : array;
}
