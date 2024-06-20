<?php

namespace oTools\network;

class IP4Network extends IP4Address
{
	protected int $mask;

	public function __construct(string $network)
	{
		if (preg_match('|^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/(\d{1,2})$|',$network,$matches))
		{
			$this->address = self::_ip_to_int($matches[1]);
			$this->mask = self::_bits_to_int((int)$matches[2]);
		}
		elseif (preg_match('|^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$|',$network,$matches))
		{
			$this->address = self::_ip_to_int($matches[1]);
			$this->mask = self::_ip_to_int($matches[2]);
		}
		else
			throw new exception('IP4 : network string syntax error \'%s\'',$network);
		if (($this->address & (~ $this->mask)) !== 0)
			throw new exception('\'%s\' not a network address',$network);
	}

	public function has(IP4Address $address) : bool
	{
		return ($address->address & $this->mask) === $this->address;
	}

	public function __toString()
	{
		return sprintf('%s/%s',self::_to_string($this->address),self::_to_string($this->mask));
	}
}