<?php

namespace oTools\http;

class server
{
	private static $http_status = array(100 => "Continue",101 => "Switching Protocols",102 => "Processing",200 => "OK",
		201 => "Created",202 => "Accepted",203 => "Non-Authoritative Information",204 => "No Content",205 => "Reset Content",
		206 => "Partial Content",207 => "Multi-Status",300 => "Multiple Choices",301 => "Moved Permanently",302 => "Found",
		303 => "See Other",304 => "Not Modified",305 => "Use Proxy",307 => "Temporary Redirect",400 => "Bad Request",
		401 => "Unauthorized",402 => "Payment Required",403 => "Forbidden",404 => "Not Found",405 => "Method Not Allowed",
		406 => "Not Acceptable",407 => "Proxy Authentication Required",408 => "Request Timeout",409 => "Conflict",
		410 => "Gone",411 => "Length Required",412 => "Precondition Failed",413 => "Request Entity Too Large",
		414 => "Request-URI Too Long",415 => "Unsupported Media Type",416 => "Requested Range Not Satisfiable",
		417 => "Expectation Failed",422 => "Unprocessable Entity",423 => "Locked",424 => "Failed Dependency",
		500 => "Internal Server Error",501 => "Not Implemented",502 => "Bad Gateway",503 => "Service Unavailable",
		504 => "Gateway Timeout",505 => "HTTP Version Not Supported",507 => "Insufficient Storage");

	public static function status($code)
	{
		if (array_key_exists($code,self::$http_status))
			header(sprintf("HTTP/1.1 %s %s",$code,self::$http_status[$code]));
		else
			throw new Exception("unknown status code");
	}

	public static function permanent_redirect($url)
	{
		self::status(301);
		header(sprintf("Location: %s",$url));
	}

	public static function found($url)
	{
		self::status(302);
		header(sprintf("Location: %s",$url));
	}

	public static function see_other($url)
	{
		self::status(303);
		header(sprintf("Location: %s",$url));
	}

	public static function temporary_redirect($url)
	{
		self::status(307);
		header(sprintf("Location: %s",$url));
	}

	public static function reload()
	{
		self::found($_SERVER['REQUEST_URI']);
	}

	public static function forbidden()
	{
		self::status(403);
	}

	public static function not_found()
	{
		self::status(404);
	}

	public static function message(int $status, string $content_type, string $message, string ...$args)
	{
		self::status($status);
		header('Content-Type: '.$content_type);
		echo vsprintf($message,$args);
	} 

	public static function json(int $status, string $message, string ...$args)
	{
		self::message($status,'Content-Type: application/json; charset=utf-8',json_encode(['message' => vsprintf($message,$args)]));
	} 

	public static function send($path,$cache = true)
	{
		if (! is_readable($path))
			self::status(403);
		elseif (is_file($path))
		{
			if (self::modified_since(filemtime($path)))
			{
				self::cache_control($cache);
				header(sprintf("Content-Type: %s",type::mime($path)));
				header(sprintf("Content-Length: %d",filesize($path)));
				readfile($path);
			}
		}
		else
			self::not_found();
	}

	public static function cache_control($cache)
	{
		if ($cache)
		{
			header('Cache-Control: public');
			header('Pragma:');
		}
		else
		{
			header('Cache-Control: no-cache');
			header('Pragma: no-cache');
		}
	}

	public static function modified_since($time,$cache = 'public')
	{
		$last_modified = gmdate("D, d M Y H:i:s",$time)." GMT";
		$if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
		if (strcasecmp($if_modified_since,$last_modified) === 0)
		{
			self::status(304);
			header('Last-modified: '.$last_modified);
			return false;
		}
		else
		{
			header('Last-modified: '.$last_modified);
			return true;
		}
	}

	public static function is_post()
	{
		return $_SERVER['REQUEST_METHOD'] === 'POST';
	}

	public static function is_get()
	{
		return $_SERVER['REQUEST_METHOD'] === 'GET';
	}

	public static function path_encode($string)
	{
		$url = '';
		$parts = explode('/',$string);
		foreach ($parts as $part)
			$url .= rawurlencode($part).'/';
		return substr($url,0,-1);
	}
}
