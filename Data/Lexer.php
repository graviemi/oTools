<?php

namespace oTools\Data;

use Iterator;

/* text lexer based on regex set */
class Lexer implements Iterator
{
	protected array $rules = [];
	protected string $data = '';
	protected int $length = 0;
	protected int $position = 0;
	protected int $index = 0;
	protected string|null $token = null;
	protected array $values = [];
	protected bool $valid = false;

	/* rules is an array of arrays
	   each array is made of
	   - one regex
	   - one string use as token identifier
	 */
	public function __construct(array $rules)
	{
		$this->rules = $rules;
	}

	public function init(string $data)
	{
		$this->data = $data;
		$this->length = strlen($data);
		$this->position = 0;
		$this->index = 0;
	}

	public function current(): mixed
	{
		return [$this->token,$this->values];
	}

	public function key(): mixed
	{
		return $this->index;
	}

	public function next(): void
	{
		$this->token = null;
		$this->values = [];
		$this->valid = $this->position < $this->length;
		if (! $this->valid)
			return ;
		foreach ($this->rules as $rule)
		{
			if (preg_match($rule[0],$this->data,$matches,0,$this->position))
			{
				$this->position += strlen($matches[0]);
				$this->index++;
				$this->token = $rule[1];
				$this->values = array_slice($matches,1);
				break;
			}
		}
	}

	public function rewind(): void
	{
		$this->position = 0;
		$this->index = 0;
		$this->next();
	}

	public function valid(): bool
	{
		return $this->valid;
	}
}