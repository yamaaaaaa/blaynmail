<?php

namespace takamoriyamashiro\Blayn;

class BlaynMail
{
	
	private $access_token = false;
	const REQUEST_OPTIONS = ['encoding' => 'UTF-8', 'escaping' => 'markup'];
	
	public function __construct($id, $password, $apikey)
	{
		$params = [$id, $password, $apikey];
		$request = xmlrpc_encode_request('authenticate.login', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$response = xmlrpc_decode($file);
		$this->access_token = $response;
	}
	
	public function logout()
	{
		$params = [$this->access_token];
		$request = xmlrpc_encode_request('authenticate.logout', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$response = xmlrpc_decode($file);
		$this->access_token = false;
		return $response;
	}
	
	private function makeContext($request,$method='POST',$header="Content-Type: text/xml")
	{
		$context = stream_context_create([
			'http' => [
				'method' => $method,
				'header' => $header,
				'content' => $request
			]
		]);
		return $context;
	}
	
	public function getToken()
	{
		return $this->access_token;
	}
	
	
	public function addUser($email, $group)
	{
		if (empty($email) || empty($group) || $this->access_token === false) {
			return false;
		}
		return false;
		$params = [$this->access_token, ['c15' => $email,'c21'=>$group]];
		$request = xmlrpc_encode_request('contact.detailCreate', $params, self::REQUEST_OPTIONS);
		$context = $this->makeContext($request);
		$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
		$data = xmlrpc_decode($file);
		
	}
}