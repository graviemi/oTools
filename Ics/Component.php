<?php

namespace oTools\Ics;

class Component
{
	protected array $properties = [];
	protected array $components = [];

	public function __construct(protected string $type) {}

	public function getType() : string
	{
		return $this->type;
	}

	public function addProperty(Property $property) : void
	{
		$this->properties[] = $property;
	}

	public function addComponent(Component $component) : void
	{
		$this->components[] = $component;
	}

	// Returns all properties, or only those matching $name (case-insensitive).
	public function getProperties(string $name = null) : array
	{
		if ($name === null)
			return $this->properties;
		$upper = strtoupper($name);
		return array_values(array_filter($this->properties, fn($p) => $p->getName() === $upper));
	}

	// Returns the first property matching $name, or null.
	public function getProperty(string $name) : ?Property
	{
		return $this->getProperties($name)[0] ?? null;
	}

	// Returns all sub-components, or only those matching $type (case-insensitive).
	public function getComponents(string $type = null) : array
	{
		if ($type === null)
			return $this->components;
		$upper = strtoupper($type);
		return array_values(array_filter($this->components, fn($c) => $c->getType() === $upper));
	}
}
