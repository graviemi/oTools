<?php

namespace oTools;

use Exception;

abstract class exceptions extends Exception
{
	public function __construct(string $string,...$args)
	{
		parent::__construct(vsprintf($string,$args));
	}
}
