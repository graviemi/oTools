<?php

namespace oTools\network;

class IP4Address extends IPAddress
{
	protected int $address = 0;

	public function __construct(string $address)
	{
		if (! preg_match('|^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$|',$address,$matches))
			throw new exception('IPv4 : string \'%s\' syntax error',$address);
		$this->address = self::_ip_to_int($matches[1]);
	}

	protected static function _ip_to_int(string $ip) : int
	{
		$value = 0;
		$bytes = explode('.',$ip);
		foreach ($bytes as $byte)
		{
			$byte = (int)$byte;
			if (($byte < 0) || ($byte > 255))
				throw new exception('IPv4 : incorrect byte value %d',$byte);
			$value = ($value << 8) + $byte;
		}
		return $value;
	}

	protected static function _bits_to_int(int $number) : int
	{
		if (($number < 0) or ($number > 32))
			throw new exception('incorrect bits number %d',$bits_number);
		$value = (1 << $number) - 1;
		$value <<= 32 - $number;
		return $value;
	}

	protected static function _to_string(int $address) : string
	{
		$bytes = [];
		for ($i = 0; $i < 4; $i++)
		{
			$bytes[] = (string)($address & 255);
			$address >>= 8;
		}
		return implode('.',array_reverse($bytes));

	}

	public function in(string ...$networks)
	{
		foreach ($networks as $string)
		{
			$network = new IP4Network($string);
			if ($network->has($this))
				return true;
		}
		return false;
	}

	public function __toString()
	{
		return self::_to_string($this->address);
	}
}