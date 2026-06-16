<?php

namespace oTools\Expressions\Evaluators;

interface IEvaluator
{
	public function evaluate(string $operator, mixed ...$values) : mixed;
}