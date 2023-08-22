<?php

namespace oTools;

use arrayaccess,
	countable,
	oTools\session\handlers\handler,
	oTools\session\identifiers\identifier;

class session implements arrayaccess,countable
{
	protected $handler;
	protected $identifier;
	protected $changed = false;
	protected $data = [];

	public function __construct(handler $handler,identifier $identifier)
	{
		$this->handler = $handler;
		$this->identifier = $identifier;
	}

	public function __destruct()
	{
		if ($this->changed)
			$this->handler->save($this->data);
		else
			$this->handler->touch();
	}

	public function exists()
	{
		return $this->identifier->exists();
	}

	public function started()
	{
		return $this->identifier->isSet();
	}

	public function id()
	{
		return $this->identifier->get();
	}

	public function start()
	{
		if ($this->started)
			return true;
		$this->handler->open($this->identifier->get());
		$this->data = $this->handler->load();
	}

	public function destroy()
	{
		$this->handler->remove();
		$this->identifier->forget();
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

	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}

	public function offsetGet($key)
	{
		return ($this->offsetExists($key))?$this->data[$key]:null;
	}

	public function offsetSet($key,$value)
	{
		$this->changed = true;
		if (is_null($key))
			$this->data[] = $value;
        else
            $this->data[$key] = $value;
	}

	public function offsetUnset($key)
	{
		$this->changed = true;
		unset($this->data[$key]);
	}

	public function count()
	{
		return count($this->data);
	}
}
