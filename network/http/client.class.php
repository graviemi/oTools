<?php

namespace oTools\network\http;

use oTools\data\text;

class client
{
	protected $ch;
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
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_HEADER => false
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

	protected function _init()
	{
		if ($this->ch === null)
		{
			$this->ch = curl_init();
			curl_setopt_array($this->ch, $this->options);
		}
	}

	protected function _url(string $url)
	{
		curl_setopt($this->ch, CURLOPT_URL, $url);
	}

	protected function _parse_header(int $position) : int
	{
		if (preg_match('|\G(http/\d.\d) (\d+) (.*)\r\n|i', $this->response, $matches, 0, $position))
			$position += strlen($matches[0]);
		else
			throw new exception('Unexpected response header.');
		while (preg_match('|\G([^ ]+) *: *([^\n\r]+)\r\n|', $this->response, $matches, 0, $position))
		{
			$this->header[strtolower($matches[1])] = $matches[2];
			$position += strlen($matches[0]);
		}
		return $position;
	}

	protected function _parse_headers(int $length)
	{
		$position = $this->_parse_header(0);
		while ($position < $length)
		{
			if (substr($this->response,$position,2) !== "\r\n")
				throw new exception('missing empty line between headers. %s',$this->_hexa(substr($this->response,$position,2)));
			$position += 2;
			$this->redirect[] = $this->header;
			$this->header = [];
			$position = $this->_parse_header($position);
		}
	}

	protected function _exec()
	{
		$this->response = curl_exec($this->ch);
		$this->infos = curl_getinfo($this->ch);
		$this->_parse_headers($this->infos['header_size'] - 2);
		$this->body = new text(substr($this->response,$this->infos['header_size']));
		if(curl_errno($this->ch))
			throw new exception('Curl: '.curl_error($this->ch));
	}

	public function setOption(int $option, $value)
	{
		$this->options[$option] = $value;
	}

	public function &getInfos() : array
	{
		return $this->infos;
	}

	public function code() : ?int
	{
		return $this->infos['http_code'] ?? null;
	}

	public function resolv(string $url) : string
	{
		$this->_init();
		$this->_url($url);
		curl_setopt($this->ch, CURLOPT_NOBODY, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_MAXREDIRS, 16);
		curl_exec($this->ch);
		$target = curl_getinfo($this->ch,CURLINFO_EFFECTIVE_URL);
		curl_setopt_array($this->ch, $this->options);
		return $target;
	}

	public function get(string $url, array $headers = []) : text
	{
		$this->_init();
		$this->_url($url);
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER,$headers);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		$this->response = curl_exec($this->ch);
		$this->infos = curl_getinfo($this->ch);
		$this->_parse_headers($this->infos['header_size'] - 2);
		$this->body = new text(substr($this->response,$this->infos['header_size']));
		if(curl_errno($this->ch))
			throw new exception('Curl: '.curl_error($this->ch));
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
			$this->_init();
			$this->_url($url);
			curl_setopt($this->ch, CURLOPT_HTTPGET, true);
			curl_setopt($this->ch, CURLOPT_FILE,$fh);
			curl_setopt($this->ch, CURLOPT_HEADER, false);
			if (is_file($path))
			{
				curl_setopt($this->ch,CURLOPT_TIMECONDITION,CURL_TIMECOND_IFMODSINCE);
				curl_setopt($this->ch,CURLOPT_TIMEVALUE,filemtime($path));
			}
			curl_exec($this->ch);
			$this->infos = curl_getinfo($this->ch);
			$this->header = [];
			$this->body = new text('');
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

	public function post(string $url, array $data, array $headers = []) : text
	{
		$this->_init();
		$this->_url($url);
		curl_setopt($this->ch,CURLOPT_POST,true);
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch,CURLOPT_POSTFIELDS,$data);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		$this->response = curl_exec($this->ch);
		$this->infos = curl_getinfo($this->ch);
		$this->_parse_headers($this->infos['header_size'] - 2);
		$this->body = new text(substr($this->response,$this->infos['header_size']));
		if(curl_errno($this->ch))
			throw new exception('Curl: '.curl_error($this->ch));
		return $this->body;
	}

	public function put(string $url, string $data, array $headers = []) : text
	{
		$headers[] = sprintf('Content-Length: %d',strlen($data));
		$this->_init();
		$this->_url($url);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST,'PUT');
		curl_setopt($this->ch, CURLOPT_HTTPHEADER,$headers);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		$this->response = curl_exec($this->ch);
		$this->infos = curl_getinfo($this->ch);
		$this->_parse_headers($this->infos['header_size'] - 2);
		$this->body = new text(substr($this->response,$this->infos['header_size']));
		if(curl_errno($this->ch))
			throw new exception('Curl: '.curl_error($this->ch));
		return $this->body;
	}

	public function delete(string $url, array $headers = []) : text
	{
		$this->_init();
		$this->_url($url);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST,'DELETE');
		curl_setopt($this->ch, CURLOPT_HTTPHEADER,$headers);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS,$data);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		$this->response = curl_exec($this->ch);
		$this->infos = curl_getinfo($this->ch);
		$this->_parse_headers($this->infos['header_size'] - 2);
		$this->body = new text(substr($this->response,$this->infos['header_size']));
		if(curl_errno($this->ch))
			throw new exception('Curl: '.curl_error($this->ch));
		return $this->body;
	}
}
