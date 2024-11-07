<?php

namespace oTools\network\http;

use oTools\data\text;

class client
{
	protected $ch;
	protected array $request_header = [];
	protected string $response;
	protected text $body;
	protected array $infos;
	protected array $header;
	protected array $redirect = [];
	protected int $error_number;
	protected string $error_message;
	protected array $options = [
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 3,
		CURLOPT_CONNECTTIMEOUT => 5
	];

	public function __construct()
	{
		$this->ch = null;
	}

	public function __destruct()
	{
		if ($this->ch !== null)
			curl_close($this->ch);
	} 

	protected function _header($ch, string $header) : int
	{
		if (preg_match('|^([^ :]+) *: *([^\n\r]+)\r\n|',$header,$matches))
			$this->header[strtolower($matches[1])] = $matches[2];
		return strlen($header);
	}

	protected function _exec(string $url)
	{
		if ($this->ch === null)
		{
			if (($this->ch = curl_init()) === false)
				throw new exception('curl init failed');	
		}
		else
			curl_reset($this->ch);
		curl_setopt_array($this->ch, $this->options);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, [$this,'_header']);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_header);
		$this->response = curl_exec($this->ch);
		$this->infos = curl_getinfo($this->ch);
		$this->body = new text($this->response);
		if(curl_errno($this->ch))
			throw new exception('Curl: '.curl_error($this->ch));
	}

	protected function resetOptions()
	{
		$this->options = [
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
			CURLOPT_CONNECTTIMEOUT => 5
		];
	}

	public function setOption(int $option, $value)
	{
		$this->options[$option] = $value;
	}

	public function setHeader(string $name, string $value)
	{
		$name = strtolower($name);
		$this->request_header[$name] = sprintf('%s: %s',$name,$value);
	}

	public function getHeader() : array
	{
		return $this->header;
	}

	public function getInfos() : array
	{
		return $this->infos;
	}

	public function code() : ?int
	{
		return $this->infos['http_code'] ?? null;
	}

	public function resolv(string $url) : string
	{
		$this->resetOptions();
		$this->setOption(CURLOPT_NOBODY, true);
		$this->_exec($url);
		$target = curl_getinfo($this->ch,CURLINFO_EFFECTIVE_URL);
		return $target;
	}

	public function get(string $url) : text
	{
		$this->resetOptions();
		$this->setOption(CURLOPT_HTTPGET, true);
		$this->setOption(CURLOPT_RETURNTRANSFER,true);
		$this->_exec($url);
		return $this->body;
	}

	public function update(string $url, string $path, ?string $destination = null) : bool
	{
		if (is_null($destination))
			$destination = $path;
		if (file_exists($destination) && (! is_file($destination)))
			throw new exception('"%s" is not a file.',$destination);
		$tmp_path = $path.'.'.uniqid();
		while (file_exists($tmp_path))
			$tmp_path = $path.'.'.uniqid();
		if (($fh = fopen($tmp_path,'w')) !== false)
		{
			$this->resetOptions();
			$this->setOption(CURLOPT_HTTPGET, true);
			$this->setOption(CURLOPT_FILE,$fh);
			if (is_file($path))
			{
				$this->setOption(CURLOPT_TIMECONDITION,CURL_TIMECOND_IFMODSINCE);
				$this->setOption(CURLOPT_TIMEVALUE,filemtime($path));
			}
			$this->_exec($url);
			if ($this->infos['http_code'] === 304)
				unlink($tmp_path);
			elseif ($this->infos['http_code'] === 200)
				rename($tmp_path,$destination);
			else
				throw new exception('Unexpected HTTP code %s.',$this->infos['http_code']);
			return ($this->infos['http_code'] === 200);
		}
		else
			throw new exception('Error opening file \'%s\' for writing.',$tmp_path);
	}

	public function post(string $url, array $data) : text
	{
		$this->resetOptions();
		$this->setOption(CURLOPT_POST,true);
		$this->setOption(CURLOPT_POSTFIELDS,$data);
		$this->setOption(CURLOPT_RETURNTRANSFER,true);
		$this->_exec($url);
		return $this->body;
	}

	public function put(string $url, string $data) : text
	{
		$this->resetOptions();
		$this->setOption(CURLOPT_CUSTOMREQUEST,'PUT');
		$this->setOption(CURLOPT_RETURNTRANSFER,true);
		$this->setOption(CURLOPT_POSTFIELDS,$data);
		$this->setHeader('content-length',strlen($data));
		$this->_exec($url);
		return $this->body;
	}

	public function delete(string $url, array $headers = []) : text
	{
		$this->resetOptions();
		$this->setOption(CURLOPT_CUSTOMREQUEST,'DELETE');
		$this->setOption(CURLOPT_RETURNTRANSFER,true);
		$this->_exec($url);
		return $this->body;
	}
}
