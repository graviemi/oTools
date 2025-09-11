<?php

namespace oTools\ticket\generators;

class uniqid implements generator
{
	protected string $seed;

	public function __construct(string $seed)
	{
		$this->seed = $seed;
	}

	public function generate() : string
	{
		return hash('sha256',uniqid('',true),false,['seed' => $this->seed]);
	}
}