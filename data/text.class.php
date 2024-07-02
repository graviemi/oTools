<?php

namespace oTools\data;

class text
{
	protected string $contents;
	protected int $length;
	protected int $position;

	public function __construct(string $contents)
	{
		$this->contents = $contents;
		$this->length = strlen($contents);
		$this->position = 0;
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function isEnd() : bool
	{
		return $this->position >= $this->length;
	}

	public function nextLine() : ?string
	{
		if ($this->isEnd())
			return null;
		if (preg_match('/\G([^\r\n]*)(\r|\n|\r\n)?/',$this->contents,$matches,0,$this->position))
		{
			$this->position += strlen($matches[0]);
			return $matches[1];
		}
		throw new exception('line match failed at %d/%d',$this->position,$this->length);
		
	}

	public function __toString() : string
	{
		return $this->contents;
	}
}
