<?php

namespace oTools\Ics;

use oTools\Data\Lexer;

class Parser
{
	// Level 1: one token per content line.
	// BEGIN_COMP and END_COMP must come before PROPERTY so they are matched first.
	// SKIP is a catch-all that consumes any line that matches nothing above (e.g. blank lines).
	// PROPERTY captures the whole line content in a single group; parseLine() re-splits it.
	// Quoted strings inside params are consumed atomically so that a ':'
	// inside a quoted param-value is not mistaken for the name/value separator.
	private static array $lineRules = [
		['#\GBEGIN:([A-Za-z][A-Za-z0-9-]*)\r?\n#', 'BEGIN_COMP'],
		['#\GEND:([A-Za-z][A-Za-z0-9-]*)\r?\n#',   'END_COMP'],
		['#\G([A-Za-z][A-Za-z0-9-]*(?:;(?:[^":;\r\n]|"[^"]*")*)*:[^\r\n]*)\r?\n#', 'PROPERTY'],
		['#\G[^\r\n]*\r?\n#', 'SKIP'],
	];

	// Level 2: tokenises the params fragment of a PROPERTY line (the part after the name).
	// Input always starts with ';', e.g. ";TZID=America/New_York;RSVP=TRUE"
	private const DATE_PROPERTIES = [
		'DTSTART', 'DTEND', 'DTSTAMP', 'DUE', 'COMPLETED',
		'CREATED', 'LAST-MODIFIED', 'RECURRENCE-ID', 'EXDATE', 'RDATE',
	];

	private static array $paramRules = [
		['#\G;([A-Za-z][A-Za-z0-9-]*)=#', 'PARAM_INTRO'],
		['#\G"([^"]*)"#',                  'QUOTED_VAL'],
		['#\G([^;,\r\n"]+)#',              'PLAIN_VAL'],
		['#\G,#',                          'COMMA'],
		['#\G.#s',                         'SKIP'],
	];

	private Lexer $lineLexer;
	private Lexer $paramLexer;

	public function __construct()
	{
		$this->lineLexer = new Lexer(self::$lineRules);
		$this->paramLexer = new Lexer(self::$paramRules);
	}

	public function parse(string $data) : VCalendar
	{
		// Strip UTF-8 BOM if present.
		if (str_starts_with($data, "\xEF\xBB\xBF"))
			$data = substr($data, 3);

		$data = $this->unfold($data);

		// Ensure the last line ends with a newline so the line rules always match.
		if (!str_ends_with($data, "\n"))
			$data .= "\n";

		$this->lineLexer->init($data);

		/** @var Component[] $stack */
		$stack = [];
		$root = null;

		foreach ($this->lineLexer as $token)
		{
			if ($token === null)
				throw new Exception('ICS parse error: no rule matched at current position');

			switch ($token->getName())
			{
				case 'BEGIN_COMP':
					$stack[] = $this->makeComponent(strtoupper($token->getValue()));
					break;

				case 'END_COMP':
					if (empty($stack))
						throw new Exception(sprintf('Unexpected END:%s without matching BEGIN', $token->getValue()));
					$comp = array_pop($stack);
					if (empty($stack))
						$root = $comp;
					else
						end($stack)->addComponent($comp);
					break;

				case 'PROPERTY':
					if (!empty($stack))
						end($stack)->addProperty($this->parseLine($token->getValue()));
					break;

				case 'SKIP':
					break;
			}
		}

		if (!$root instanceof VCalendar)
			throw new Exception('ICS data does not contain a VCALENDAR component');

		return $root;
	}

	// RFC 5545 §3.1: unfold continuation lines (CRLF followed by a single WSP).
	private function unfold(string $data) : string
	{
		return preg_replace('/\r\n[ \t]/', '', $data);
	}

	private function makeComponent(string $type) : Component
	{
		return match($type) {
			'VCALENDAR' => new VCalendar(),
			'VEVENT'    => new VEvent(),
			'VTODO'     => new VTodo(),
			'VJOURNAL'  => new VJournal(),
			'VTIMEZONE' => new VTimezone(),
			'STANDARD'  => new Standard(),
			'DAYLIGHT'  => new Daylight(),
			'VFREEBUSY' => new VFreeBusy(),
			'VALARM'    => new VAlarm(),
			default     => new Component($type),
		};
	}

	// Builds a Property from the full content of a PROPERTY line :
	//   NAME[;PARAM=value[;...]]:VALUE
	// The ':' separator is the first one that is not inside a quoted param value.
	private function parseLine(string $line) : Property
	{
		// Locate the name/value separator (first unquoted ':').
		$len = strlen($line);
		$inQuote = false;
		for ($i = 0; $i < $len; $i++)
		{
			$c = $line[$i];
			if ($c === '"')
				$inQuote = !$inQuote;
			elseif ($c === ':' && !$inQuote)
				break;
		}
		if ($i === $len)
			throw new Exception("Malformed property line: $line");

		$head     = substr($line, 0, $i);
		$rawValue = substr($line, $i + 1);

		// Split head into name and (optional) params fragment starting with ';'.
		$semi = strpos($head, ';');
		if ($semi === false)
		{
			$name     = $head;
			$paramStr = '';
		}
		else
		{
			$name     = substr($head, 0, $semi);
			$paramStr = substr($head, $semi);
		}

		$params = [];

		if ($paramStr !== '')
		{
			$this->paramLexer->init($paramStr);
			$currentParam = null;

			foreach ($this->paramLexer as $token)
			{
				if ($token === null)
					continue;

				switch ($token->getName())
				{
					case 'PARAM_INTRO':
						$currentParam = strtoupper($token->getValue());
						if (!isset($params[$currentParam]))
							$params[$currentParam] = [];
						break;

					case 'QUOTED_VAL':
					case 'PLAIN_VAL':
						if ($currentParam !== null)
							$params[$currentParam][] = $token->getValue();
						break;

					case 'COMMA':
					case 'SKIP':
						break;
				}
			}
		}

		$upperName = strtoupper($name);
		$isDate = in_array($upperName, self::DATE_PROPERTIES, true);
		return new Property($upperName, $params, $rawValue, $isDate);
	}
}
