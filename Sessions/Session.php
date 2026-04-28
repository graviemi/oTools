<?php

namespace oTools\Sessions;

use ArrayAccess,
	Countable,
	oTools\Sessions\Handlers\HandlerInterface,
	oTools\Sessions\Identifiers\IdentifierInterface;

class Session implements ArrayAccess,Countable
{
	protected HandlerInterface $handler;
	protected IdentifierInterface $identifier;
	protected bool $changed = false;
	protected array $data = [];

	public function __construct(HandlerInterface $handler,IdentifierInterface $identifier)
	{
		$this->handler = $handler;
		$this->identifier = $identifier;
	}

	public function __destruct()
	{
		if ($this->started())
		{
			if ($this->changed)
				$this->handler->save($this->data);
			else
				$this->handler->touch();
		}
	}

	public function setArray(array &$data) : void
	{
		$this->data =& $data;
	}

	public function exists() : bool
	{
		return $this->identifier->exists();
	}

	public function started() : bool
	{
		return $this->identifier->isSet();
	}

	public function id() : string
	{
		return $this->identifier->get();
	}

	public function start() : void
	{
		if (! $this->started())
		{
			$this->handler->open($this->identifier->get());
			$this->data = $this->handler->load();
		}
	}

	public function destroy() : void
	{
		if ($this->exists())
		{
			if (! $this->started())
				$this->start();
			$this->handler->remove();
			$this->identifier->forget();
		}
	}

	public function touch()
	{
		$this->handler->touch();
		$this->identifier->touch();
	}

	public function __get($key)
	{
		return $this->offsetGet($key);
	}

	public function __set($key,$value)
	{
		$this->offsetSet($key,$value);
	}

	public function offsetExists($key) : bool
	{
		$this->start();
		return isset($this->data[$key]);
	}

	public function offsetGet($key) : mixed
	{
		$this->start();
		return ($this->offsetExists($key))?$this->data[$key]:null;
	}

	public function offsetSet($key,$value) : void
	{
		$this->start();
		$this->changed = true;
		if (is_null($key))
			$this->data[] = $value;
		else
			$this->data[$key] = $value;
	}

	public function offsetUnset($key) : void
	{
		$this->start();
		$this->changed = true;
		unset($this->data[$key]);
	}

	public function count() : int
	{
		$this->start();
		return count($this->data);
	}

	public function toArray() : array
	{
		$this->start();
		return $this->data;
	}
}
