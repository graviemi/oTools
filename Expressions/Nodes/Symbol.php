<?php

namespace oTools\Expressions\Nodes;

use oTools\Expressions\Evaluators\IEvaluator;

class Symbol extends Node
{
	public function __construct(protected string $name)
	{}

	public function evaluate(IEvaluator $evaluator) : mixed
	{
		return $evaluator->evaluate('symbol',$this->name);
	}
}