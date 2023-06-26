<?php

namespace oTools;

use arrayaccess;

class template implements arrayaccess
{
	protected $path;
	protected $values;

	public function __construct(string $path, array $values)
	{
		$this->path = $path;
		$this->values = $values;
	}

	public function __get($name)
	{
		return $this->values[$name] ?? null;
	}

	public function __set($name,$value)
	{
		$this->values[$name] = $value;
	}

	public function offsetExists($key)
	{
		return isset($this->values[$key]);
	}

	public function offsetGet($key)
	{
		return $this->__get($key);
	}

	public function offsetSet($key,$value)
	{
		$this->__set($key,$value);
	}

	public function offsetUnset($key)
	{}

	public function __toString()
	{
		if (is_readable($this->path) && ob_start())
		{
			require $this->path;
			return ob_get_clean();
		}
		return '';
	}
}
