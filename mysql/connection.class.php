<?php

namespace oTools\mysql;

use mysqli,
	arrayobject;

class connection
{
	protected $address;
	protected $login;
	protected $password;
	protected $mysqli;
	protected $database;
	protected $charset;
	protected $ssl_key;
	protected $ssl_cert;
	protected $ssl_ca;
	public $last_query = ''; 
	public $show = false;

	public function __construct(string $address,string $login,string $password,string $ssl_key = null,string $ssl_cert = null,string $ssl_ca = null)
	{
		$this->address = $address;
		$this->login = $login;
		$this->password = $password;
		$this->mysqli = null;
		$this->database = null;
		$this->charset = null;
		$this->ssl_key = $ssl_key;
		$this->ssl_cert = $ssl_cert;
		$this->ssl_ca = $ssl_ca;
	}

	protected function _connect()
	{
		if (is_null($this->mysqli))
		{
			if ((! is_null($this->ssl_key)) && (! is_null($this->ssl_cert)) && (! is_null($this->ssl_ca)))
			{
				$this->mysqli = new mysqli();
				$this->mysqli->ssl_set($this->ssl_key,$this->ssl_cert,$this->ssl_ca,null,null);
				$this->mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT,false);
				$this->mysqli->real_connect($this->address,$this->login,$this->password,null,null,null,MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
				// workaround https://bugs.php.net/bug.php?id=76714
				while (openssl_error_string());
				// end
			}
			else
				$this->mysqli = new mysqli($this->address,$this->login,$this->password);
			if ($this->mysqli->connect_error)
				throw new exception(sprintf('connection error to %s@%s',$this->login,$this->address));
		}
	}

	public function close()
	{
		$this->mysqli->close();
	}

	public function status()
	{
		$this->_connect();
		$status = new arrayobject();
		$rows = $this->query('SHOW STATUS');
		foreach ($rows as $row)
			$status[$row['Variable_name']] = $row['Value'];
		return $status;
	}

	public function base($name)
	{
		return new base($name,$this);
	}

	public function database_select($name)
	{
		if ($this->database !== $name)
		{
			$this->_connect();
			if (!$this->mysqli->select_db($this->database = $name))
				throw new exception(sprintf('unable to select db \'%s\'',$name));
		}
	}

	public function database_catalog()
	{
		return $this->query('SHOW DATABASES',result::ROW);
	}

	public function database_exists($name)
	{
		$result = $this->query(sprintf('SHOW DATABASES LIKE \'%s\'',$name));
		return (count($result) === 1);
	}

	public function database_drop($name,$if_exists = true)
	{
		return $this->query(sprintf('DROP DATABASE%s `%s`',($if_exists)?' IF EXISTS':'',$name));
	}

	public function database_create($name,$if_not_exists = true)
	{
		return $this->query(sprintf('CREATE DATABASE%s `%s`',($if_not_exists)?' IF NOT EXISTS':'',$name));
	}

	public function database_grant_all($name,$user,$pass,$origin,$grant_option = false)
	{
		return $this->query(sprintf('GRANT ALL PRIVILEGES ON `%s`.* TO \'%s\'@\'%s\' IDENTIFIED BY \'%s\'%s',$name,$user,$origin,$pass,($grant_option)?' WITH GRANT OPTION':''));
	}

	public function database_grant_select($name,$user,$pass,$origin,$grant_option = false)
	{
		return $this->query(sprintf('GRANT SELECT ON `%s`.* TO \'%s\'@\'%s\' IDENTIFIED BY \'%s\'%s',$name,$user,$origin,$pass,($grant_option)?' WITH GRANT OPTION':''));
	}

	public function set_charset($charset) : self
	{
		if ($this->charset !== $charset)
		{
			$this->_connect();
			if (! $this->mysqli->set_charset($charset))
				throw new exception('Error setting character set %s: %s',$charset,array($this->mysqli->error));
			$this->charset = $charset;
		}
		return $this;
	}

	public function get_charset()
	{
		if (! is_null($this->mysqli))
			return $this->mysqli->character_set_name();
		return null;
	}

	public function disable_key_check()
	{
		$this->query('SET FOREIGN_KEY_CHECKS=0');
	}

	public function enable_key_check()
	{
		$this->query('SET FOREIGN_KEY_CHECKS=1');
	}

	public function id()
	{
		if (is_null($this->mysqli))
			return null;
		return $this->mysqli->insert_id;
	}

	public function rows()
	{
		if (is_null($this->mysqli))
			return null;
		return $this->mysqli->affected_rows;
	}

	public function query(string $query, int $mode = result::ASSOC)
	{
		if ($this->show)
		{
			printf("%s\n",$query);
			return 0;
		}
		$this->_connect();
		$this->last_query = $query;
		$result = $this->mysqli->query($query);
		if ($result === false)
			throw new exception($this->mysqli->error.PHP_EOL.$query);
		if (is_a($result,'mysqli_result'))
			return new result($result, $mode);
		return $result;
	}
}
