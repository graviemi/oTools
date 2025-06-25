<?php

namespace oTools\network;

use oTools\network\http\client;

class solid
{
	protected $host;
	protected $http_client;

	public function __construct(string $host, string $user, string $password, client|null $client = null)
	{
		$this->host = $host;
		$this->http_client = $client ?? (new client());
		$this->http_client->setHeader('X-IPM-Username',base64_encode($user));
		$this->http_client->setHeader('X-IPM-Password',base64_encode($password));
	}

	protected function _url(string $verb, array $parameters)
	{
		$url = sprintf('https://%s/rest/%s',$this->host,$verb);
		if (count($parameters) > 0)
			$url .= '?'.http_build_query($parameters);
		return $url;
	}

	protected function _get(string $url) : array
	{
		$response = $this->http_client->get($url);
		if ($this->http_client->code() === 200)
			return json_decode($response,true,10,JSON_THROW_ON_ERROR);
		throw new exception('HTTP request error code %d : %s',$this->http_client->code(),$response);
	}

	protected function _post(string $url, array $data = []) : array
	{
		$response = $this->http_client->post($url,$data);
		if ($this->http_client->code() === 201)
			return json_decode($response,true,10,JSON_THROW_ON_ERROR);
		throw new exception('HTTP request error code %d : %s',$this->http_client->code(),$response);
	}

	protected function _delete(string $url) : array
	{
		$response = $this->http_client->delete($url);
		if ($this->http_client->code() === 200)
			return json_decode($response,true,10,JSON_THROW_ON_ERROR);
		throw new exception('HTTP request error code %d : %s',$this->http_client->code(),$response);
	}

	public function ip_address_list(array $parameters = []) : array
	{
		return $this->_get($this->_url('ip_address_list',$parameters));
	}

	public function ip_alias_list(int $ip_id, array $parameters = []) : array
	{
		$params['ip_id'] = $ip_id;
		foreach ($parameters as $name => $value)
			$params[$name] = $value;
		return $this->_get($this->_url('ip_alias_list',$params));
	}

	public function ip_alias_count(int $ip_id, array $parameters = []) : array
	{
		$parameters['ip_id'] = $ip_id;
		return $this->_get($this->_url('ip_alias_count',$parameters));
	}

	public function ip_alias_add(int $ip_id, string $ip_name) : array
	{
		return $this->_post($this->_url('ip_alias_add',['ip_id' => $ip_id,'ip_name' => $ip_name]));
	}

	public function ip_alias_delete(int $ip_name_id) : array
	{
		echo $this->_url('ip_alias_delete',['ip_name_id' => $ip_name_id]).PHP_EOL;
		return $this->_delete($this->_url('ip_alias_delete',['ip_name_id' => $ip_name_id]));
	}
}