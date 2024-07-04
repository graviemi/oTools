<?php

namespace oTools\data;

use iterator;

class text implements iterator
{
	protected string $contents;
	protected int $length;
	protected int $position = 0;
	protected int $index = 0;
	protected string $line = '';
	protected int $increment = 0;

	public function __construct(string $contents)
	{
		$this->contents = $contents;
		$this->length = strlen($contents);
	}

	public function current()
	{
		return $this->line;
	}

	public function key()
	{
		return $this->index;
	}

	public function next()
	{
		$this->position += $this->increment;
		if (preg_match('/\G([^\r\n]*)(\r|\n|\r\n)?/',$this->contents,$matches,0,$this->position))
		{
			$this->line = $matches[1];
			$this->increment = strlen($matches[0]);
			$this->index++;
		}
	}

	public function rewind()
	{
		$this->position = 0;
		$this->index = 0;
		if (preg_match('/\G([^\r\n]*)(\r|\n|\r\n)?/',$this->contents,$matches,0,$this->position))
		{
			$this->line = $matches[1];
			$this->increment = strlen($matches[0]);
		}
	}

	public function valid()
	{
		return $this->position < $this->length;
	}

	public function __toString() : string
	{
		return $this->contents;
	}
}
