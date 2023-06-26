<?php

namespace oTools\mysql;

class base
{
	protected $name;
	protected $connection;

	public function __construct(string $name, connection $connection)
	{
		$this->name = $name;
		$this->connection = $connection;
	}

	public function connection()
	{
		return $this->connection;
	}

	public function rows()
	{
		return $this->connection->rows();
	}

	public function request()
	{
		return new request($this);
	}

/*	public function table_catalog()
	{
		return $this->query('SHOW FULL TABLES WHERE `Table_type` = \'BASE TABLE\'',result::ROW);
	}

	public function table_exists($name)
	{
		$result = $this->query(sprintf('SHOW FULL TABLES WHERE `Tables_in_%s` = \'%s\' AND `Table_type` = \'BASE TABLE\'',$this->name,$this->escape($name)));
		return (count($result) === 1);
	}

	public function table_trunk($name)
	{
		return $this->query(sprintf('TRUNCATE TABLE `%s`',$this->escape($name)));
	}

	public function table_drop($name,$if_exists = true)
	{
		return $this->query(sprintf('DROP TABLE%s `%s`',($if_exists)?' IF EXISTS':'',$this->escape($name)));
	}*/

	public function query($query,$mode = result::ASSOC)
	{
		$this->connection->database_select($this->name);
		return $this->connection->query($query, $mode);
	}

	public function __toString()
	{
		return $this->name;
	}
}
