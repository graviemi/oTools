<?php

namespace oTools\Ics;

class Property
{
	public function __construct(
		protected string $name,
		protected array $params,
		protected string $rawValue
	) {}

	public function getName() : string
	{
		return $this->name;
	}

	public function getRawValue() : string
	{
		return $this->rawValue;
	}

	public function getParams() : array
	{
		return $this->params;
	}

	// Returns the list of values for a parameter, or null if absent.
	// e.g. getParam('TZID') → ['America/New_York']
	// e.g. getParam('ENCODING') → ['QUOTED-PRINTABLE']
	public function getParam(string $name) : array|null
	{
		return $this->params[strtoupper($name)] ?? null;
	}
}
