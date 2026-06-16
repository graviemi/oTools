<?php

namespace oTools\Expressions\Evaluators;

use DateTimeInterface,
	oTools\Exception;

class Calculate implements IEvaluator
{
	protected array $_operations = [];
	protected array $_symbolTable = [];

	public function __construct(array $symbolTable)
	{
		$this->_symbolTable = $symbolTable;
		$this->_operations = [
			// résolution d'un symbole depuis la table des symboles
			'symbol' => fn($name) => array_key_exists($name, $this->_symbolTable)
				? $this->_symbolTable[$name]
				: throw new Exception("Symbole inconnu : %s", $name),
			// logique
			'||' => fn($x, $y) => $x || $y,
			'&&' => fn($x, $y) => $x && $y,
			'!'  => fn($x) => ! $x,
			// comparaison
			'='  => fn($x, $y) => $x == $y,
			'<'  => fn($x, $y) => $x < $y,
			'>'  => fn($x, $y) => $x > $y,
			'<=' => fn($x, $y) => $x <= $y,
			'>=' => fn($x, $y) => $x >= $y,
			// bit à bit
			'|'  => fn($x, $y) => $x | $y,
			'&'  => fn($x, $y) => $x & $y,
			// arithmétique ('+' et '-' sont unaires ou binaires)
			'+'  => fn(...$a) => count($a) === 1 ? +$a[0] : $a[0] + $a[1],
			'-'  => fn(...$a) => count($a) === 1 ? -$a[0] : $a[0] - $a[1],
			'*'  => fn($x, $y) => $x * $y,
			'/'  => fn($x, $y) => $y == 0
				? throw new Exception("Division par zéro")
				: $x / $y,
			'%'  => fn($x, $y) => $y == 0
				? throw new Exception("Modulo par zéro")
				: $x % $y,
			'^'  => fn($x, $y) => $x ** $y,
			// fonctions
			'abs'   => fn($x) => abs($x),
			'sqrt'  => fn($x) => sqrt($x),
			'floor' => fn($x) => floor($x),
			'ceil'  => fn($x) => ceil($x),
			'round' => fn($x, $p = 0) => round($x, (int)$p),
			'min'   => fn(...$a) => min($a),
			'max'   => fn(...$a) => max($a),
			'pow'   => fn($x, $y) => $x ** $y,
			// timestamp (secondes Unix) d'une date
			'ts'    => fn($d) => $d instanceof DateTimeInterface
				? $d->getTimestamp()
				: throw new Exception("ts attend un DateTimeInterface"),
		];
	}

	public function evaluate(string|null $operator, mixed ...$values) : mixed
	{
		// valeur littérale
		if ($operator === null)
			return $values[0] ?? null;
		if (isset($this->_operations[$operator]))
			return $this->_operations[$operator](...$values);
		throw new Exception("Opérateur inconnu : %s", $operator);
	}
}