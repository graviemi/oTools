<?php

namespace oTools\mysql;

use iterator,
	arrayaccess,
	countable,
	mysqli_result;

class result implements iterator,arrayaccess,countable
{
	const ASSOC = 0;
	const ROW = 1;
	const BOTH = 2;

	protected $mode;
	protected $result;
	protected $index;
	protected $current;

	public function __construct(mysqli_result $result, int $mode = self::ASSOC)
	{
		$this->mode = $mode;
		$this->result = $result;
		$this->index = 0;
		$this->_fetch();
	}

	protected function _fetch()
	{
		if ($this->mode === self::ASSOC)
			$this->current = $this->result->fetch_assoc();
		elseif ($this->mode === self::ROW)
			$this->current = $this->result->fetch_row();
		else
			$this->current = $this->result->fetch_array();

	}

	public function offsetExists($index): bool
	{
		return $index < $this->result->num_rows;
	}

	public function offsetGet($index): mixed
	{
		$this->result->data_seek($this->index = $index);
		$this->_fetch();
		return $this->current;
	}

	public function offsetSet($key,$value): void
	{}

	public function offsetUnset($key): void
	{}

	public function count(): int
	{
		return $this->result->num_rows;
	}

	public function current(): mixed
	{
		return $this->current;
	}

	public function key(): mixed
	{
		return $this->index;
	}

	public function next(): void
	{
		$this->index++;
		$this->_fetch();
	}

	public function rewind(): void
	{
		$this->result->data_seek($this->index = 0);
		$this->_fetch();
	}

	public function valid(): bool
	{
		return $this->index < $this->result->num_rows;
	}

	public function json_output()
	{
		$comma = '';
		echo '[';
		foreach ($this as $row)
		{
			echo $comma.json_encode($row,JSON_PARTIAL_OUTPUT_ON_ERROR);
			$comma = ',';
		}
		echo ']';
	}

	public function __toString()
	{
		foreach ($this as $row)
			print_r($row);
	}
}
