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
	protected Token|null $token = null;
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
		return $this->token;
	}

	public function key(): mixed
	{
		return $this->index;
	}

	public function next(): void
	{
		$this->token = null;
		$this->valid = $this->position < $this->length;
		if (! $this->valid)
			return ;
		foreach ($this->rules as $rule)
		{
			if (preg_match($rule[0],$this->data,$matches,0,$this->position))
			{
				$this->position += strlen($matches[0]);
				$this->index++;
				$this->token = new Token($rule[1], $matches[1] ?? null, $rule[2] ?? null);
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