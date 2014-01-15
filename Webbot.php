<?php
/**
 * Webbot - PHP Webbot by Shay Anderson
 *
 * Webbot is free software and is distributed WITHOUT ANY WARRANTY
 *
 * @version $v: 1.0 $;
 * @copyright Copyright (c) 2012 ShayAnderson.com
 * @license http://www.gnu.org/licenses/gpl.html GPL License
 * @link http://www.shayanderson.com/php/php-webbot-class-for-harvesting-web-data.htm
 *
 * @example
 * $bot = new Webbot;
 * if($bot->crawl('http://[site name here]'))
 * {
 *		if($bot->isData())
 *		{
 *			echo $bot->getData();
 *		}
 *		else
 *		{
 *			echo 'No data found';
 *		}
 * }
 * else
 * {
 *		echo 'Crawl error: ' . $bot->getErrorLast();
 * }
 */

/**
 * Webbot Class
 *
 * @package Webbot
 * @name Webbot
 * @author Shay Anderson 4.12
 */
final class Webbot
{
	/**
	 * Crawl methods
	 */
	const METHOD_DEFAULT = 0;
	const METHOD_GET = 1;
	const METHOD_HEAD = 2;
	const METHOD_POST = 3;

	/**
	 * Crawl data
	 *
	 * @var string
	 */
	private $__data;

	/**
	 * Crawl errors
	 *
	 * @var array
	 */
	private $__errors = array();

	/**
	 * Crawl status info (url, content_type, http_code, ...)
	 *
	 * @var array
	 */
	private $__status = array();

	/**
	 * Cooke file path, ex:  '/var/www/example/cookie.txt'
	 *
	 * @var string
	 */
	public $cookie_file_path;

	/**
	 * Include header with body
	 *
	 * @var bool
	 */
	public $header_get = false;

	/**
	 * Max redirects allowed for single crawl request
	 *
	 * @var int
	 */
	public $max_redirects = 4;

	/**
	 * Proxy, ex: '[IP address]:8080'
	 *
	 * @var string
	 */
	public $proxy;

	/**
	 * Use proxy HTTP tunnel
	 *
	 * @var bool
	 */
	public $proxy_http_tunnel = false;

	/**
	 * Referer, ex: 'http://example.com'
	 *
	 * @var string
	 */
	public $referer;

	/**
	 * Sleep before crawl for x seconds (zero for no sleep)
	 *
	 * @var int
	 */
	public $sleep_before_crawl = 0;

	/**
	 * Crawl request timeout seconds
	 *
	 * @var int
	 */
	public $timeout = 20;

	/**
	 * User agent used for crawl request
	 *
	 * @var string
	 */
	public $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';

	/**
	 * Crawl request
	 *
	 * @param string $url
	 * @param int $method
	 * @param array $send_data
	 * @return bool (false on error)
	 */
	public function crawl($url, $method = self::METHOD_DEFAULT, $send_data = array())
	{
		if(empty($url))
		{
			$this->__errors[] = 'Failed to crawl, invalid crawl URI';
			return false;
		}
		if(!function_exists('curl_init'))
		{
			$this->__errors[] = 'Failed to crawl, cURL Library required';
			return false;
		}
		if((int)$this->sleep_before_crawl > 0)
		{
			sleep((int)$this->sleep_before_crawl);
		}
		$ch = curl_init();
		$qs = NULL;
		if(is_array($send_data) && count($send_data) > 0)
		{
			$tmp = array();
			// foreach($send_data as $k => $v)
			// {
			// 	$tmp = $k . '=' . urlencode($v);
			// }
			// $qs = implode('&', $tmp);
			// unset($tmp);
			$qs = http_build_query($send_data);
		}
		if($method == self::METHOD_HEAD) // get head only
		{
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true); // no body
		}
		else
		{
			if($method == self::METHOD_GET)
			{
				if(!empty($qs))
				{
					$url .= '?' . $qs;
				}
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				curl_setopt($ch, CURLOPT_POST, false);
			}
			else if($method == self::METHOD_POST)
			{
				if(!empty($qs))
				{
					curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
				}
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPGET, false);
			}
			curl_setopt($ch, CURLOPT_HEADER, $this->header_get);
			curl_setopt($ch, CURLOPT_NOBODY, false); // get body
		}
		if(!empty($this->proxy))
		{
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $this->proxy_http_tunnel);
		}
		if($this->cookie_file_path !== NULL)
		{
			if(!is_file($this->cookie_file_path) || !is_writable($this->cookie_file_path))
			{
				$this->__errors[] = 'Failed to crawl, cannot find and/or write to cookie file "'
					. $this->cookie_file_path . '"';
			}
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file_path);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file_path);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		if(!empty($this->user_agent))
		{
			curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		if(!empty($this->referer))
		{
			curl_setopt($ch, CURLOPT_REFERER, $this->referer);
		}
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, $this->max_redirects);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$this->__data = curl_exec($ch);
		$this->__status = curl_getinfo($ch);
		$is_success = true;
		if($this->__data === false || curl_errno($ch) > 0)
		{
			$this->__data = NULL;
			$this->__errors[] = curl_error($ch) . ' (' . curl_errno($ch) . ')';
			$is_success = false;
		}
		curl_close($ch);
		return $is_success;
	}

	/**
	 * Find string in text
	 *
	 * @param string $text
	 * @param string $search_string
	 * @return bool (true if search string found in text)
	 */
	public function find($text, $search_string)
	{
		return strpos($text, $search_string) !== false;
	}

	/**
	 * Find string with regex pattern in text
	 *
	 * @param string $text
	 * @param string $search_pattern
	 * @return bool (true if search pattern found in text)
	 */
	public function findRegex($text, $search_pattern)
	{
		return preg_match("/{$search_pattern}/siU", $text) > 0;
	}

	/**
	 * Crawl data getter
	 *
	 * @return string
	 */
	public function getData()
	{
		return $this->__data;
	}

	/**
	 * Last error getter
	 *
	 * @return string
	 */
	public function getErrorLast()
	{
		$err = NULL;
		if(count($this->__errors) > 0)
		{
			$err = end($this->__errors);
			reset($this->__errors);

		}
		return $err;
	}

	/**
	 * Crawl errors getter
	 *
	 * @return array ('err1', 'err2', [, ...])
	 */
	public function getErrors()
	{
		return $this->__errors;
	}

	/**
	 * Crawl status getter
	 *
	 * @return array ('url' => x, 'content_type' => y, 'http_code' => z, [, ...])
	 */
	public function getStatus()
	{
		return $this->__status;
	}

	/**
	 * Crawl status HTTP code getter
	 *
	 * @return int
	 */
	public function getStatusHttpCode()
	{
		if(isset($this->__status['http_code']))
		{
			return (int)$this->__status['http_code'];
		}
		return 0;
	}

	/**
	 * Is data retrieved flag getter
	 *
	 * @return bool
	 */
	public function isData()
	{
		return strlen(trim($this->__data)) > 0;
	}

	/**
	 * Parse data between x (start delimiter) and y (stop delimiter)
	 *
	 * @param string $text
	 * @param string $start
	 * @param string $stop
	 * @param bool $include_delimiters
	 * @return string
	 */
	public function parseBetween($text, $start, $stop, $include_delimiters = false)
	{
		return $this->parseString($this->parseString($text, $start, true, $include_delimiters),
			$stop, false, $include_delimiters);
	}

	/**
	 * Parse data using regex pattern
	 *
	 * @param string $text
	 * @param string $start_pattern (ex: '<table.*>')
	 * @param string $stop_pattern (ex: '<\/table>')
	 * @param bool $include_delimiters
	 * @return string
	 */
	public function parseBetweenRegex($text, $start_pattern, $stop_pattern, $include_delimiters = false)
	{
		preg_match_all("/{$start_pattern}(.*){$stop_pattern}/siU", $text, $matches, PREG_PATTERN_ORDER);
		if(!$include_delimiters && isset($matches[1][0]))
		{
			return $matches[1][0];
		}
		else if($include_delimiters && isset($matches[0]))
		{
			return $matches[0][0];
		}
		return array();
	}

	/**
	 * Parse data using regex pattern and return array
	 *
	 * @param string $text
	 * @param string $start_pattern (ex: '<table.*>')
	 * @param string $stop_pattern (ex: '<\/table>')
	 * @param bool $include_delimiters
	 * @return array ('match0', 'match1', [, ...])
	 */
	public function parseBetweenRegexArray($text, $start_pattern, $stop_pattern, $include_delimiters = false)
	{
		preg_match_all("/{$start_pattern}(.*){$stop_pattern}/siU", $text, $matches, PREG_PATTERN_ORDER);
		if(!$include_delimiters && isset($matches[1]))
		{
			return $matches[1];
		}
		else if($include_delimiters && isset($matches[0]))
		{
			return $matches[0];
		}
		return array();
	}

	/**
	 * Parse string out of data before/after needle (string)
	 *
	 * @param string $str
	 * @param mixed $needle
	 * @param bool $fetch_after_needle (false will fetch before needle)
	 * @param bool $include_needle
	 * @return string
	 */
	public function parseString($text, $needle, $fetch_after_needle = true, $include_needle = false)
	{
		$parsed = '';
		if(!empty($text))
		{
			$parse_str = strtolower($text);
			$needle = strtolower($needle);
			if(!$fetch_after_needle) // get string before needle
			{
				$pos = strpos($parse_str, $needle) + ( !$include_needle ? 0 : strlen($needle) );
				if($pos !== false)
				{
					$parsed = substr($text, 0, $pos);
				}
			}
			else // get string after needle
			{
				$pos = strpos($parse_str, $needle) + ( !$include_needle ? strlen($needle) : 0 );
				if($pos !== false)
				{
					$parsed = substr($text, $pos, strlen($text));
				}
			}
		}
		return $parsed;
	}

	/**
	 * Print array
	 *
	 * @param array $arr
	 * @return void
	 */
	public function printArray($arr = array())
	{
		echo '<pre>' . print_r($arr, true) . '</pre>';
	}

	/**
	 * Remove string based on patterns
	 *
	 * @param string $text
	 * @param string $start_pattern
	 * @param string $stop_pattern
	 * @param bool $remove_delimiters (true will remove delimiters used in start/stop patterns)
	 * @return string
	 */
	public function removeRegex($text, $start_pattern, $stop_pattern, $remove_delimiters = true)
	{
		return preg_replace("/({$start_pattern})(.*)({$stop_pattern})/siU", ( $remove_delimiters ? '' : '$1$3' ), $text);
	}

	/**
	 * Remove/strip tags, ex: using '<td.*>','<\/td>' as open/close would do: '<td id="td0">value0</td>' => 'value0'
	 *
	 * @param string $text
	 * @param string $tag_open_pattern
	 * @param string $tag_close_pattern
	 * @return string
	 */
	public function removeTagsRegex($text, $tag_open_pattern, $tag_close_pattern)
	{
		return preg_replace("/({$tag_open_pattern})(.*)({$tag_close_pattern})/siU", '$2', $text);
	}
}
?>

