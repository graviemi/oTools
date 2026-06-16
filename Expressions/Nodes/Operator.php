<?php

namespace oTools\Expressions\Nodes;

use oTools\Expressions\Evaluators\IEvaluator;

class Operator extends Node
{
	protected string $operator;
	protected array $operands;

	public function __construct(string $operator, Node ...$operands)
	{
		$this->operator = $operator;
		$this->operands = $operands;
	}

	public function evaluate(IEvaluator $evaluator) : mixed
	{
		return $evaluator->evaluate($this->operator,...array_map(fn($o) => $o->evaluate($evaluator), $this->operands));
	}
}