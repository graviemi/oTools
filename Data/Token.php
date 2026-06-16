<?php

namespace oTools\Data;

class Token
{
	public function __construct(protected string $name, protected string|null $value = null, protected string|null $type = null)
	{}

	public function getName() : string
	{
		return $this->name;
	}

	public function getValue() : string|null
	{
		return $this->value;
	}

	public function getType() : string
	{
		return $this->type ?? 'unknown';
	}
}