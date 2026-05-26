<?php

namespace oTools\Ics;

use oTools\Data\Lexer;

class Parser
{
	// Level 1: one token per content line.
	// BEGIN_COMP and END_COMP must come before PROPERTY so they are matched first.
	// SKIP is a catch-all that consumes any line that matches nothing above (e.g. blank lines).
	private static array $lineRules = [
		['#\GBEGIN:([A-Za-z][A-Za-z0-9-]*)\r?\n#', 'BEGIN_COMP'],
		['#\GEND:([A-Za-z][A-Za-z0-9-]*)\r?\n#',   'END_COMP'],
		// Three capture groups: property name | params string | raw value.
		// The params section is separated from the name by ';' characters.
		// Quoted strings inside params are consumed atomically so that a ':'
		// inside a quoted param-value is not mistaken for the name/value separator.
		['#\G([A-Za-z][A-Za-z0-9-]*)((?:;(?:[^":;\r\n]|"[^"]*")*)*):([^\r\n]*)\r?\n#', 'PROPERTY'],
		['#\G[^\r\n]*\r?\n#', 'SKIP'],
	];

	// Level 2: tokenises the params fragment of a PROPERTY line (the part after the name).
	// Input always starts with ';', e.g. ";TZID=America/New_York;RSVP=TRUE"
	private static array $paramRules = [
		['#\G;([A-Za-z][A-Za-z0-9-]*)=#', 'PARAM_INTRO'],
		['#\G"([^"]*)"#',                  'QUOTED_VAL'],
		['#\G([^;,\r\n"]+)#',              'PLAIN_VAL'],
		['#\G,#',                          'COMMA'],
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

		foreach ($this->lineLexer as [$token, $values])
		{
			if ($token === null)
				throw new Exception('ICS parse error: no rule matched at current position');

			switch ($token)
			{
				case 'BEGIN_COMP':
					$stack[] = $this->makeComponent(strtoupper($values[0]));
					break;

				case 'END_COMP':
					if (empty($stack))
						throw new Exception(sprintf('Unexpected END:%s without matching BEGIN', $values[0]));
					$comp = array_pop($stack);
					if (empty($stack))
						$root = $comp;
					else
						end($stack)->addComponent($comp);
					break;

				case 'PROPERTY':
					if (!empty($stack))
						end($stack)->addProperty($this->parseLine($values[0], $values[1], $values[2]));
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

	// Builds a Property from the three parts captured by the PROPERTY token.
	// $name     : property name (e.g. "DTSTART")
	// $paramStr : params fragment starting with ';' (e.g. ";TZID=Europe/Paris"), may be empty
	// $rawValue : raw value string after ':' (e.g. "20240615T100000")
	private function parseLine(string $name, string $paramStr, string $rawValue) : Property
	{
		$params = [];

		if ($paramStr !== '')
		{
			$this->paramLexer->init($paramStr);
			$currentParam = null;

			foreach ($this->paramLexer as [$token, $values])
			{
				switch ($token)
				{
					case 'PARAM_INTRO':
						$currentParam = strtoupper($values[0]);
						if (!isset($params[$currentParam]))
							$params[$currentParam] = [];
						break;

					case 'QUOTED_VAL':
					case 'PLAIN_VAL':
						if ($currentParam !== null)
							$params[$currentParam][] = $values[0];
						break;

					case 'COMMA':
						// separator between multiple param values — no action needed
						break;
				}
			}
		}

		return new Property(strtoupper($name), $params, $rawValue);
	}
}
