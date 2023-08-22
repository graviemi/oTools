<?php

namespace oTools\session\identifiers;

class cookie implements identifier
{
	protected $id = null;
	protected $name;
	protected $lifetime;
	protected $domain;
	protected $path;
	protected $secure;
	protected $httponly;

	public function __construct(string $name,int $lifetime,string $domain,string $path = '/',bool $secure = true,bool $httponly = false)
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
		return (! is_null($this->id));
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
				throw new exception('cannot create cookie');
		}
	}

	public function forget()
	{
		if ($this->isSet())
		{
			if (! setcookie($this->name,null,time() - 60,$this->path,$this->domain,$this->secure,$this->httponly))
				throw new exception('cannot remove cookie');
		}
	}
}

