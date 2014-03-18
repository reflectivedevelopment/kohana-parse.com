<?php defined('SYSPATH') OR die('No direct script access.'); 

class Kohana_Parse {

	protected $_config = NULL;

	public static function factory($config='default')
	{
		return new Parse($config);
	}

	public function __construct($config_name='default')
	{
		$config = Kohana::$config->load('parse');
		if ( ! array_key_exists($config_name, $config))
			throw new Kohana_Exception('Invalid Config!');
		$config = $config[$config_name];

		if ( ! array_key_exists('application_id', $config)
			|| ! array_key_exists('api_key', $config)
			|| ! array_key_exists('master_key', $config)
			|| ! array_key_exists('parse_url', $config))
		{
			throw new Kohana_Exception('Invalid Config!');
		}

		$this->_config = $config;
	}

	protected function _getRequest($part)
	{
		$request = Request::factory($this->_config['parse_url'] . $part);

		// Application ID
		$request->headers('X-Parse-Application-Id', $this->_config['application_id']);

		// Key
		if ($this->_config['master_key'] != NULL)
		{
			$request->headers('X-Parse-Master-Key', $this->_config['master_key']);
		}
		else
		{
			$request->headers('X-Parse-REST-API-Key', $this->_config['api_key']);
			/* do we have a session token? */
			$session = Session::instance();
			$token = $session->get('sessionToken', NULL);
			if ($token != NULL)
			{
				$request->headers('X-Parse-Session-Token', $token);
			}
		}

		// JSON Header
		$request->headers('Content-Type', 'application/json');

		return $request;
	}	

	protected function _doRequest($request)
	{
		// Do request
		$response = $request->execute();

		if ($response->status() < 200 || $response->status() >= 300)
		{
			// Check to see if session token has expired. If so, throw exception
			if ($this->_config['master_key'] == NULL)
			{
				$decoded = json_decode($response->body());
				if ($decoded->error == 'invalid session')
				{
					$session = Session::instance();
					$session->delete('sessionToken');
				}
				throw new Parse_Exception_Invalid_Session();
			}
			throw new Parse_Exception($response);
		}

		return $response;
	}

	public function login($username, $password)
	{
		$request = $this->_getRequest("login");

		try {
			$request->post('username', $username);
			$request->post('password', $password);

			$response = $this->_doRequest($request);

			$response = json_decode($response->body());

			$token = $response->sessionToken;

			$session = Session::instance();
			$session->set('sessionToken', $token);

			return True;
		} catch (Exception $e) {}

		return False;
	}

	public function logout()
	{
		$session = Session::instance();
		$session->delete('sessionToken');
	}

	public function objectCreate()
	{
		
	}

	public function objectGet()
	{

	}

	public function objectPut()
	{

	}

	public function objectQuery()
	{

	}

	public function objectDelete()
	{

	}
}
