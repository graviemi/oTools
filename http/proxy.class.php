<?php

namespace oTools\http;

class proxy
{
	protected function headers($handle,$string)
	{
//		echo $string;
/*		if (strlen($string) > 2)
			header(substr($string,0,-2));*/
		return strlen($string);
	}

	protected function body($handle,$string)
	{
		echo $string;
		return strlen($string);
	}

	public function output($host = null, $ip = null)
	{
//		header('Content-Type: text/plain');
		if (is_null($host))
			$host = $_SERVER['HTTP_HOST'];
		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on'))
		{
			$protocol = 'https';
			$port = 443;
		}
		else
		{
			$protocol = 'http';
			$port = 80;
		}
		$headers = array();
		foreach ($_SERVER as $key => $value)
		{
			if ($key === 'HTTP_HOST')
				$headers[] = 'Host: '.$host;
			elseif (strncmp($key,'HTTP_',5) === 0)
				$headers[] = sprintf('%s: %s',strtr(ucwords(strtolower(strtr(substr($key,5),'_',' '))),' ','-'),$value);
		}

//		printf('<h1>%s://%s%s</h1>',$protocol,$host,$_SERVER['REQUEST_URI']);
		$handle = curl_init(sprintf('%s://%s%s',$protocol,$host,$_SERVER['REQUEST_URI']));
//		curl_setopt($handle,CURLINFO_HEADER_OUT,true);
		if (!is_null($ip))
			curl_setopt($handle,CURLOPT_RESOLVE,array($host.':'.$port.':'.$ip));
		curl_setopt($handle,CURLOPT_CONNECTTIMEOUT,3);
		curl_setopt($handle,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($handle,CURLOPT_HTTPHEADER,$headers);
		curl_setopt($handle,CURLOPT_HEADERFUNCTION,array($this,'headers'));
		curl_setopt($handle,CURLOPT_WRITEFUNCTION,array($this,'body'));
		curl_exec($handle);
		if (curl_errno($handle) !== 0)
			printf('<h1>%s</h1>'.PHP_EOL,curl_error($handle));
//		printf("<pre>%s</pre>\n",htmlspecialchars(print_r(curl_getinfo($handle,CURLINFO_HEADER_OUT),true)));
	}
}
