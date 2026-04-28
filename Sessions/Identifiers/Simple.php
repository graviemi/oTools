<?php

namespace oTools\Sessions\Identifiers;

class Simple implements IdentifierInterface
{
	protected $id;

	public function __construct(string $id)
	{
		$this->id = $id;
	}

	public function exists()
	{
		return true;
	}

	public function isSet()
	{
		return true;
	}

	public function get()
	{
		return $this->id;
	}

	public function touch()
	{}

	public function forget()
	{}
}

