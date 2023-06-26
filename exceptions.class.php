<?php

namespace oTools;

use Exception;

abstract class oExceptions extends Exception
{
	public function __construct(string $string,...$args)
	{
		parent::__construct(vsprintf($string,$args));
	}
}
