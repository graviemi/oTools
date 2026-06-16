<?php

namespace oTools\Expressions\Nodes;

use DateTimeImmutable;
use oTools\Expressions\Evaluators\IEvaluator;

class Value extends Node
{
	protected array $conversions;

	public function __construct(protected string $value, protected string $type)
	{
		$this->conversions = [
			'integer' => fn(string $v) => (int)$v,
			'float' => fn(string $v) => (float)$v,
			'date' => fn(string $v) => new DateTimeImmutable($v)
		];
	}

	protected function _type_integer(string $v)
	{
		return (int)$v;
	}

	protected function _type_float(string $v)
	{
		return (float)$v;
	}

	protected function _type_date(string $v)
	{
		return new DateTimeImmutable($v);
	}

	public function evaluate(IEvaluator $evaluator) : mixed
	{
		$convert = sprintf('_type_%s',$this->type);
		if (method_exists($this,$convert))
			return $evaluator->evaluate(null,$this->$convert($this->value));
		return $evaluator->evaluate(null,$this->value);
	}
}