<?php

namespace oTools\mysql;

class request
{
	protected static $regex = [
		['|^/#(\d+)|','reference'],
		['|^"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|','string'],
		['|^/([\w\d_-]+)|','identifier'],
		['|^:([\w\d_-]+)|','parameter'],
		['|^(.[^"/:#]*)|','other']
	];
	protected $base;
	protected $sql = '';
	protected $symbols = [];
	protected $identifiers = [];
	protected $values = [];
	protected $index;
	protected $count;

	public function __construct(base $base)
	{
		$this->base = $base;
		$this->index = 0;
		$this->count = 0;
	}

	protected function _reference(int $index) : string
	{
		if (isset($this->identifiers[$index]))
			return '`'.$this->identifiers[$index].'`';
		throw new exception('unknown identifier reference #%d',$index);
	}

	protected function _identifier(string $str) : string
	{
		$this->identifiers[] = $str;
		return '`'.$str.'`';
	}

	protected function _string(string $str) : string
	{
		return '\''.$str.'\'';
	}

	protected function _parameter(string $str) : string
	{
		if (isset($this->symbols[$str]))
			$value = $this->symbols[$str];
		elseif ($this->index < $this->count)
			$value = $this->symbols[$str] = $this->values[$this->index++];
		else
			throw new exception('no value found for reference "%s"',$str);
		if (is_null($value))
			return 'NULL';
		if (is_bool($value))
			return ($value)?'1':'0';
		if (is_string($value))
			return '\''.addcslashes($value,'\'\\').'\'';
		return (string)$value;
	}

	protected function _other(string $str) : string
	{
		return $str;
	}

	protected function _parse($string)
	{
		$request = '';
		$index = 0;
		$length = strlen($string);
		while ($index < $length)
		{
			foreach (self::$regex as $rule)
			{
				if (preg_match($rule[0],substr($string,$index),$matches))
				{
					$request .= call_user_func([$this,'_'.$rule[1]],$matches[1]);
					$index += strlen($matches[0]);
					break;
				}
			}
		}
		return $request;
	}

	protected function _map(&$tables)
	{
		foreach ($tables as $name)
		{
			if (! isset($this->tables_map[$name]))
			{
				$this->tables[] = $name;
				$this->tables_map[$name] = 0;
			}
		}
	}

	public function sql($sql)
	{
		$this->sql = $sql;
		return $this;
	}

	public function bind($name,$value)
	{
		$this->symbols[$name] = $value;
		return $this;
	}

	public function binds(array $data, array $map = []) : self
	{
		foreach ($data as $key => $value)
		{
			if (isset($map[$key]))
				$this->symbols[$map[$key]] = $value;
			else
				$this->symbols[$key] = $value;
		}
		return $this;
	}

	public function sbinds(...$values) : self
	{
		$this->values = $values;
		$this->count = count($values);
		$this->index = 0;
		return $this;
	}

	public function exec()
	{
		return $this->base->query($this->__toString());
	}

	public function __toString()
	{
		return $this->_parse($this->sql);
	}
}
