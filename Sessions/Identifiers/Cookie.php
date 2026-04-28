<?php

namespace oTools\Sessions\Identifiers;

use oTools\oToolsException;

class Cookie implements IdentifierInterface
{
	protected string|null $id = null; // session ID value
	protected string $name; // cookie name
	protected int $lifetime; // cookie time to live
	protected string $domain; // cookie definition domain
	protected string $path; // cookie definition path
	protected bool $secure; // true if only on secure HTTP (https)
	protected bool $httponly; // true if only on HTTP protocol

	public function __construct(string $name,int $lifetime,string $domain,string $path = '/',bool $secure = true,bool $httponly = true)
	{
		$this->name = $name;
		$this->lifetime = $lifetime;
		$this->domain = $domain;
		$this->path = $path;
		$this->secure = $secure;
		$this->httponly = $httponly;
	}

	public function exists()
	{
		return isset($_COOKIE[$this->name]);
	}

	public function isSet()
	{
		return ($this->id !== null);
	}

	public function get()
	{
		if (! $this->isSet())
		{
			if (isset($_COOKIE[$this->name]))
				$this->id = $_COOKIE[$this->name];
			else
				$this->id = strtr(base64_encode(random_bytes(30)),'/+','_-');
			$this->touch();
		}
		return $this->id;
	}

	public function touch()
	{
		if ($this->isSet())
		{
			if (! setcookie($this->name,$this->id,time() + $this->lifetime,$this->path,$this->domain,$this->secure,$this->httponly))
				throw new oToolsException('cannot create cookie "%s"',$this->name);
		}
	}

	public function forget()
	{
		\PHPFullcalendar\_::debug('memcached remove : %s %s',($this->isSet())?'set':'pas set',$this->name);
		if ($this->isSet())
		{
			if (! setcookie($this->name,'',time() - 60,$this->path,$this->domain,$this->secure,$this->httponly))
				throw new oToolsException('cannot remove cookie "%s"',$this->name);
			$this->id = null;
		}
	}
}

