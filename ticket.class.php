<?php

namespace oTools;

use oTools\ticket\handlers\handler
	,oTools\ticket\generators\generator;


class ticket
{
	protected string $ticket = '';
	protected handler $handler;

	public function __construct(handler $handler)
	{
		$this->handler = $handler;
		$this->ticket = '';
	}

	public function new(generator $generator) : string
	{
		return $this->ticket = $generator->generate();
	}

	public function set(string $ticket) : void
	{
		$this->ticket = $ticket;
	}

	public function put(array $data) : void
	{
		if ($this->ticket === '')
			throw new exception('undefined ticket');
		$this->handler->put($this->ticket,$data);
	}

	public function get() : array
	{
		if ($this->ticket === '')
			throw new exception('undefined ticket');
		return $this->handler->get($this->ticket);
	}

	public function __toString()
	{
		return $this->ticket;
	}
}