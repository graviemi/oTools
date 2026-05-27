<?php

namespace oTools\Ics;

use DateTimeImmutable,
	DateTimeZone,
	Exception;

class Property
{
	protected Component|null $parent = null;

	public function __construct(
		protected string $name,
		protected array $params,
		protected string $rawValue,
		protected bool $isDate = false
	) {}

	public function setParent(Component $parent) : void
	{
		$this->parent = $parent;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getRawValue() : string
	{
		return $this->rawValue;
	}

	public function getParams() : array
	{
		return $this->params;
	}

	// Returns the list of values for a parameter, or null if absent.
	// e.g. getParam('TZID') → ['America/New_York']
	// e.g. getParam('ENCODING') → ['QUOTED-PRINTABLE']
	public function getParam(string $name) : array|null
	{
		return $this->params[strtoupper($name)] ?? null;
	}

	// Returns the date as UTC, or null if this property is not a date property.
	// Three cases per RFC 5545:
	//   - All-day (YYYYMMDD)          → midnight UTC
	//   - UTC datetime (Z suffix)     → direct conversion
	//   - Local datetime + TZID param → timezone resolved via PHP then VTIMEZONE
	//   - Floating datetime (no TZID) → treated as UTC (no context to convert)
	public function getUTC() : DateTimeImmutable|null
	{
		if (!$this->isDate)
			return null;

		$value = $this->rawValue;

		// All-day date: YYYYMMDD
		if (preg_match('/^\d{8}$/', $value))
		{
			$dt = DateTimeImmutable::createFromFormat('Ymd', $value, new DateTimeZone('UTC'));
			return $dt === false ? null : $dt->setTime(0, 0, 0);
		}

		// DATETIME: YYYYMMDDTHHmmss[Z]
		if (!preg_match('/^\d{8}T\d{6}(Z?)$/', $value, $m))
			return null;

		// UTC datetime
		if ($m[1] === 'Z')
		{
			$dt = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
			return $dt === false ? null : $dt;
		}

		// Floating or TZID-based local datetime
		$tzid = $this->params['TZID'][0] ?? null;

		if ($tzid === null)
		{
			// Floating: no timezone info available, return as-is treated as UTC
			$dt = DateTimeImmutable::createFromFormat('Ymd\THis', $value, new DateTimeZone('UTC'));
			return $dt === false ? null : $dt;
		}

		$tz = $this->resolveTimezone($tzid);
		$dt = DateTimeImmutable::createFromFormat('Ymd\THis', $value, $tz);
		return $dt === false ? null : $dt->setTimezone(new DateTimeZone('UTC'));
	}

	// Resolves a TZID string to a DateTimeZone.
	// Tries PHP's built-in database first, then searches VTIMEZONE components
	// in the parent VCalendar, and falls back to UTC.
	private function resolveTimezone(string $tzid) : DateTimeZone
	{
		try {
			return new DateTimeZone($tzid);
		} catch (Exception) {}

		// Traverse parent chain to find VCalendar
		$node = $this->parent;
		while ($node !== null && !$node instanceof VCalendar)
			$node = $node->getParent();

		if ($node !== null)
		{
			foreach ($node->getComponents('VTIMEZONE') as $vtz)
			{
				if ($vtz->getProperty('TZID')?->getRawValue() !== $tzid)
					continue;
				$offset = $this->extractVTimezoneOffset($vtz);
				if ($offset !== null)
					return new DateTimeZone($offset);
			}
		}

		return new DateTimeZone('UTC');
	}

	// Extracts the UTC offset from a VTIMEZONE component for this property's date.
	// Finds the STANDARD or DAYLIGHT sub-component whose DTSTART is the most recent
	// one not exceeding the local datetime of this property.
	private function extractVTimezoneOffset(Component $vtz) : string|null
	{
		// Parse our own value as a naive local datetime for comparison
		$localDt = DateTimeImmutable::createFromFormat('Ymd\THis', substr($this->rawValue, 0, 15), new DateTimeZone('UTC'));

		$bestOffset = null;
		$bestTs = null;

		$subcomps = array_merge(
			$vtz->getComponents('STANDARD'),
			$vtz->getComponents('DAYLIGHT')
		);

		foreach ($subcomps as $sub)
		{
			$dtstart = $sub->getProperty('DTSTART')?->getRawValue();
			$tzoffsetto = $sub->getProperty('TZOFFSETTO')?->getRawValue();
			if ($dtstart === null || $tzoffsetto === null)
				continue;

			$subDt = DateTimeImmutable::createFromFormat('Ymd\THis', $dtstart, new DateTimeZone('UTC'));
			if ($subDt === false)
				continue;

			// Skip sub-components that start after our date
			if ($localDt !== false && $subDt > $localDt)
				continue;

			if ($bestTs === null || $subDt > $bestTs)
			{
				$bestOffset = $tzoffsetto;
				$bestTs = $subDt;
			}
		}

		// No sub-component found before our date: use the first available
		if ($bestOffset === null)
		{
			foreach ($subcomps as $sub)
			{
				$bestOffset = $sub->getProperty('TZOFFSETTO')?->getRawValue();
				if ($bestOffset !== null)
					break;
			}
		}

		if ($bestOffset === null)
			return null;

		// Convert ICS offset (+0100) to PHP format (+01:00)
		if (preg_match('/^([+-])(\d{2})(\d{2})$/', $bestOffset, $m))
			return sprintf('%s%s:%s', $m[1], $m[2], $m[3]);

		return $bestOffset;
	}
}
