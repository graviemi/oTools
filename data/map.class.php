<?php

namespace oTools\data;

use iterator,
	arrayaccess,
	countable;

class map implements iterator,arrayaccess,countable
{
	protected $data;
	protected $index;

	public function __construct()
	{
		$this->data = [];
		$this->index = 0;
	}

	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}

	public function offsetGet($key)
	{
		return isset($this->data[$key])?$this->data[$key]:null;
	}

	public function offsetSet($key,$value)
	{
		if (is_null($key))
			$this->data[] = $value;
        else
            $this->data[$key] = $value;
	}

	public function offsetUnset($key)
	{
		unset($this->data[$key]);
	}

	public function count()
	{
		return count($this->data);
	}

	public function exists($key)
	{
		return array_key_exists($key,$this->data);
	}

	public function get($key)
	{
		return $this->data[$key];
	}

	public function set($key,$value)
	{
		$this->data[$key] = $value;
	}

	public function current()
	{
		return current($this->data);
	}

	public function key()
	{
		return key($this->data);
	}

	public function next()
	{
		$this->index++;
		next($this->data);
	}

	public function rewind()
	{
		$this->index = 0;
		reset($this->data);
	}

	public function valid()
	{
		return ($this->index < count($this->data));
	}

	public function implode(string $glue) : string
	{
		return implode($glue,$this->data);
	}
}
