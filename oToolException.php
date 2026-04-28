<?php

namespace oTools;

use Exception;

class oToolsException extends Exception
{
	public function __construct(string $string,...$args)
	{
		parent::__construct(vsprintf($string,$args));
	}
}
