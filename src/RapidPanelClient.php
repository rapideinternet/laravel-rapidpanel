<?php namespace Rapide\RapidPanel;

use Rapide\RapidPanel\Models\RapidPanelModel;

class RapidPanelClient
{
	private $debug;
	private $cache;
	private $socketConfig;

	public function __construct($socketConfig)
	{
		if(!isset($socketConfig['port'])) {
			$socketConfig['port'] = config('rapidpanel.port');
		}
		if(!isset($socketConfig['timeout'])) {
			$socketConfig['timeout'] = config('rapidpanel.timeout');
		}

		$this->socketConfig = $socketConfig;
	}

	private function setCache($key, $value)
	{
		$this->cache[$key] = $value;
	}

	private function getCache($key)
	{
		if(isset($this->cache[$key]) == true)
		{
			return $this->cache[$key];
		}

		return null;
	}

	public function hashPassword($password)
	{
		return md5($password);
	}

	public function passwordIsHashed($password)
	{
		if(strlen($password) == 32) // 32 is php's md5 default length
		{
			return true;
		}

		return false;
	}

	public function safe_b64encode($string)
	{
		$data = base64_encode($string);
		$data = str_replace(array('+','/','='), array('-','_',''), $data);

		return $data;
	}

	public function safe_b64decode($string)
	{
		$data = str_replace(array('-','_'), array('+','/'), $string);
		$mod4 = strlen($data) % 4;

		if ($mod4)
		{
			$data .= substr('====', $mod4);
		}

		return base64_decode($data);
	}

	public function encrypt($value, $skey, $encType = "")
	{
		if(!$value)
		{
			return false;
		}

		switch($encType)
		{
			case "BASE_64/MCRYPT_RIJNDAEL_256+MCRYPT_MODE_ECB":
				$text = $value;
				$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
				$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
				$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $skey, $text, MCRYPT_MODE_ECB, $iv);
				return trim($this->safe_b64encode($crypttext));
				break;
		}

		return "";
	}

	public function decrypt($value, $skey, $encType = "")
	{
		if(!$value)
		{
			return false;
		}

		switch($encType)
		{
			case "BASE_64/MCRYPT_RIJNDAEL_256+MCRYPT_MODE_ECB":
				$crypttext = $this->safe_b64decode($value);
				$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
				$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
				$decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $skey, $crypttext, MCRYPT_MODE_ECB, $iv);
				return trim($decrypttext);
				break;
		}

		return "";
	}

	private function request($config, $arguments)
	{
		$errnum = "";
		$errstr = "";

		$socket = fsockopen($config['host'], $config['port'], $errnum, $errstr, $config['timeout']);

		if (is_resource($socket))
		{
			// read buffer from socket
			fputs($socket, json_encode($arguments));

			$data = "";

			while (!feof($socket))
			{
				$data.= fgets($socket, 1024);
			}
			fclose($socket);

			// format the data
			return (array)@json_decode(trim($data));
		}

		return null;
	}

	private function invoke($username = null, $passwordHash = "", $arguments = array())
	{
		if($username == null || $passwordHash == null)
		{
			return null;
		}

		$cacheHash = md5($username.$passwordHash.json_encode($arguments));

		// try to load from in-memory cache
		if(($payload = $this->getCache($cacheHash)) != null)
		{
			return $payload;
		}

		$encType = "BASE_64/MCRYPT_RIJNDAEL_256+MCRYPT_MODE_ECB";

		$request = array(
			'payload' => $this->encrypt(json_encode($arguments), $passwordHash, $encType),
			'username' => $username,
			'enctype' => $encType
		);

		$response = $this->request($this->socketConfig, $request);

		$payload = (array)@json_decode($this->decrypt($response['payload'], $passwordHash, $encType));

		// debug?
		if(isset($payload['debug']) == true)
		{
			$this->debug = $payload['debug'];
		}

		// data?
		if(isset($payload['data']) == true)
		{
			$objects = array();

			if(@is_array($payload['data']) == true)
			{
				foreach($payload['data'] as $rawObject)
				{
					// make the returned objects extend the Model class
					$object = new RapidPanelModel();
					$object->setAttributes($rawObject);

					$objects[] = $object;
				}

				$this->setCache($cacheHash, $objects);

				return $objects;
			}

			$this->setCache($cacheHash, $payload['data']);

			return $payload['data'];
		}


		return null;
	}

	public function fetch($username, $password, $arguments)
	{
		if(($list = $this->invoke($username, $password, $arguments)) === null)
		{
			return array();
		}

		return $list;
	}

	public function fetchOne($username, $password, $arguments)
	{
		$list = $this->invoke($username, $password, $arguments);

		if(is_array($list) == true)
		{
			return end($list);
		}

		return null;
	}

	public function delete($username, $password, $arguments)
	{
		return $this->invoke($username, $password, $arguments);
	}

	public function modify($username, $password, $arguments)
	{
		return $this->invoke($username, $password, $arguments);
	}

	public function getDebug()
	{
		$debug = array();

		foreach(explode("\n", $this->debug) as $line)
		{
			$type = substr($line, 0, strpos($line, ":"));
			$message = substr($line, strpos($line, ":")+1);

			$debug[] = array('type' => trim($type), 'message' => trim($message));
		}


		return $debug;
	}

	public function getDomains($username, $password, $arguments)
	{
		return $this->fetch($username, $password, $arguments);
	}

}