<?php

namespace oTools\network;

class IP4Mask extends IP4Address
{
	public function __construct(int $number)
	{
		$this->address = self::_bits_to_int($number);
	}
}