<?php

namespace oTools\network;

use oTools\network\http\client;

class solid
{
	protected $host;
	protected $credentials;
	protected $http_client;

	public function __construct(string $host, string $user, string $password)
	{
		$this->host = $host;
		$this->credentials = [
			sprintf('X-IPM-Username: %s',base64_encode($user)),
			sprintf('X-IPM-Password: %s',base64_encode($password))
		];
		$this->http_client = new client();
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
		$response = $this->http_client->get($url,$this->credentials);
		if ($this->http_client->code() === 200)
			return json_decode($response,true,10,JSON_THROW_ON_ERROR);
		throw new exception('HTTP request error code %d : %s',$this->http_client->code(),$response);
	}

	protected function _post(string $url, array $data = []) : array
	{
		$response = $this->http_client->post($url,$data,$this->credentials);
		if ($this->http_client->code() === 201)
			return json_decode($response,true,10,JSON_THROW_ON_ERROR);
		throw new exception('HTTP request error code %d : %s',$this->http_client->code(),$response);
	}

	protected function _delete(string $url) : array
	{
		$response = $this->http_client->delete($url,$this->credentials);
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
		$parameters['ip_id'] = $ip_id;
		return $this->_get($this->_url('ip_alias_list',$parameters));
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
		return $this->_delete($this->_url('ip_alias_delete',['ip_name_id' => $ip_name_id]));
	}
}