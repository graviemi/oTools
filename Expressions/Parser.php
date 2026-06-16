<?php

namespace oTools\Expressions;

use oTools\Expressions\Nodes\Node,
	oTools\Expressions\Nodes\Operator,
	oTools\Expressions\Nodes\Value,
	oTools\Expressions\Nodes\Symbol;

use oTools\Data\Lexer;

/* Recursive-descent parser building an AST of Node instances.
   Grammar (from lowest to highest precedence) :
     or       → and       ( '||' and       )*
     and      → compare   ( '&&' compare   )*
     compare  → bitOr     ( ('='|'<'|'>'|'<='|'>=') bitOr )?
     bitOr    → bitAnd    ( '|'  bitAnd    )*
     bitAnd   → additive  ( '&'  additive  )*
     additive → term      ( ('+'|'-') term )*
     term     → power     ( ('*'|'/'|'%') power )*
     power    → unary     ( '^' power      )?            // right associative
     unary    → ('!'|'-'|'+') unary | primary
     primary  → number | symbol | function args ')' | '(' or ')'
     args     → ( or ( ',' or )* )?
*/
class Parser
{
	protected static array $regexp = [
		['/\G(\s+)/','blank'],
		['/\G\(/','opar'],
		['/\G\)/','cpar'],
		['/\G,/','comma'],
		['/\G(<=|>=|\|\||&&)/','operator'],
		['#\G([<>=+*/&|!^%-])#','operator'],
		['/\G(\d{4,4}-\d{2,2}-\d{2,2}T\d{2,2}:\d{2,2}:\d{2,2}(?:Z|[+-]\d{2,2}:\d{2,2})?)/','value','date'],
		['/\G(\d*\.\d+)/','value', 'float'],
		['/\G(\d+)/','value', 'integer'],
		['/\G([a-z]+)\(/','function'],
		['/\G([a-z]+)/','symbol'],
		['/\G"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/','string']
	]; 
	protected Lexer $lexer;

	public function __construct()
	{
		$this->lexer = new Lexer(self::$regexp);
	}

	public function parse(string $expression): Node
	{
		$this->lexer->init($expression);
		$this->advance();
		$node = $this->parseOr();
		if ($this->lexer->valid())
			throw new \RuntimeException("Jeton inattendu : ".$this->currentValue());
		return $node;
	}

	/* advance to next non-blank token */
	protected function advance(): void
	{
		do {
			$this->lexer->next();
		} while ($this->lexer->valid() && $this->currentToken() === 'blank');
	}

	protected function currentToken(): ?string
	{
		if (! $this->lexer->valid())
			return null;
		return $this->lexer->current()->getName();
	}

	protected function currentValue(): mixed
	{
		if (! $this->lexer->valid())
			return null;
		return $this->lexer->current()->getValue();
	}

	protected function currentType(): string
	{
		if (! $this->lexer->valid())
			return 'unknown';
		return $this->lexer->current()->getType();
	}

	protected function expect(string $token): void
	{
		if ($this->currentToken() !== $token)
			throw new \RuntimeException("Attendu '$token', obtenu '".($this->currentToken() ?? 'EOF')."'");
		$this->advance();
	}

	/* or → and ( '||' and )* */
	protected function parseOr(): Node
	{
		$left = $this->parseAnd();
		while ($this->currentToken() === 'operator' && $this->currentValue() === '||')
		{
			$this->advance();
			$right = $this->parseAnd();
			$left = new Operator('||',$left,$right);
		}
		return $left;
	}

	/* and → compare ( '&&' compare )* */
	protected function parseAnd(): Node
	{
		$left = $this->parseCompare();
		while ($this->currentToken() === 'operator' && $this->currentValue() === '&&')
		{
			$this->advance();
			$right = $this->parseCompare();
			$left = new Operator('&&',$left,$right);
		}
		return $left;
	}

	/* compare → bitOr ( ('='|'<'|'>'|'<='|'>=') bitOr )? */
	protected function parseCompare(): Node
	{
		$left = $this->parseBitOr();
		if ($this->currentToken() === 'operator'
			&& in_array($this->currentValue(),['=','<','>','<=','>='],true))
		{
			$op = $this->currentValue();
			$this->advance();
			$right = $this->parseBitOr();
			return new Operator($op,$left,$right);
		}
		return $left;
	}

	/* bitOr → bitAnd ( '|' bitAnd )* */
	protected function parseBitOr(): Node
	{
		$left = $this->parseBitAnd();
		while ($this->currentToken() === 'operator' && $this->currentValue() === '|')
		{
			$this->advance();
			$right = $this->parseBitAnd();
			$left = new Operator('|',$left,$right);
		}
		return $left;
	}

	/* bitAnd → additive ( '&' additive )* */
	protected function parseBitAnd(): Node
	{
		$left = $this->parseAdditive();
		while ($this->currentToken() === 'operator' && $this->currentValue() === '&')
		{
			$this->advance();
			$right = $this->parseAdditive();
			$left = new Operator('&',$left,$right);
		}
		return $left;
	}

	/* additive → term ( ('+' | '-') term )* */
	protected function parseAdditive(): Node
	{
		$left = $this->parseTerm();
		while ($this->currentToken() === 'operator'
			&& in_array($this->currentValue(),['+','-'],true))
		{
			$op = $this->currentValue();
			$this->advance();
			$right = $this->parseTerm();
			$left = new Operator($op,$left,$right);
		}
		return $left;
	}

	/* term → power ( ('*'|'/'|'%') power )* */
	protected function parseTerm(): Node
	{
		$left = $this->parsePower();
		while ($this->currentToken() === 'operator'
			&& in_array($this->currentValue(),['*','/','%'],true))
		{
			$op = $this->currentValue();
			$this->advance();
			$right = $this->parsePower();
			$left = new Operator($op,$left,$right);
		}
		return $left;
	}

	/* power → unary ( '^' power )?  right associative */
	protected function parsePower(): Node
	{
		$left = $this->parseUnary();
		if ($this->currentToken() === 'operator' && $this->currentValue() === '^')
		{
			$this->advance();
			$right = $this->parsePower();
			return new Operator('^',$left,$right);
		}
		return $left;
	}

	/* unary → ('!'|'-'|'+') unary | primary */
	protected function parseUnary(): Node
	{
		if ($this->currentToken() === 'operator'
			&& in_array($this->currentValue(),['!','-','+'],true))
		{
			$op = $this->currentValue();
			$this->advance();
			$operand = $this->parseUnary();
			return new Operator($op,$operand);
		}
		return $this->parsePrimary();
	}

	/* primary → number | symbol | function args ')' | '(' or ')' */
	protected function parsePrimary(): Node
	{
		$token = $this->currentToken();
		$value = $this->currentValue();

		if ($token === 'value')
		{
			$type = $this->currentType();
			$this->advance();
			return new Value($value,$type);
		}
		if ($token === 'symbol')
		{
			$this->advance();
			return new Symbol($value);
		}
		if ($token === 'function')
		{
			$this->advance();
			$args = [];
			if ($this->currentToken() !== 'cpar')
			{
				$args[] = $this->parseOr();
				while ($this->currentToken() === 'comma')
				{
					$this->advance();
					$args[] = $this->parseOr();
				}
			}
			$this->expect('cpar');
			return new Operator($value,...$args);
		}
		if ($token === 'opar')
		{
			$this->advance();
			$node = $this->parseOr();
			$this->expect('cpar');
			return $node;
		}
		throw new \RuntimeException("Jeton inattendu : ".($token ?? 'EOF'));
	}
}
