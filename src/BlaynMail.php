<?php

namespace yamaaaaaa\Blayn;

class BlaynMail
{
	
	const REQUEST_OPTIONS = ['encoding' => 'UTF-8', 'escaping' => 'markup'];
	const SEARCH_LIMIT = 20;
	
	const MODE_XMLRPC = 'mode_xmlrpc';
	const MODE_HTTPS = 'mode_https';
	
	private $access_token = false;
	private $errors = [];
	private $mode = '';
	
	public function __construct($id, $password, $apikey, $mode = 'mode_https')
	{
		$this->mode = $mode;
		if ($this->mode == self::MODE_HTTPS) {
			$response = $this->post('https://api.bme.jp/rest/1.0/authenticate/login', [
				'username' => $id,
				'password' => $password,
				'api_key' => $apikey
			]);
			
			if (!isset($response["accessToken"])) {
				return false;
			}
			$this->access_token = $response["accessToken"];
		} else {
			$params = [$id, $password, $apikey];
			$request = xmlrpc_encode_request('authenticate.login', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$response = xmlrpc_decode($file);
			$this->access_token = $response;
		}
	}
	
	/**
	 * @param $url
	 * @param array $params
	 * @return bool|mixed
	 * 2018.07現在、パラメータf=jsonを渡してもXMLがresponceされる場合あり
	 */
	private function post($url, $params = [])
	{
		$request = new \HTTP_Request2($url);
		try {
			$request->setMethod(\HTTP_Request2::METHOD_POST);
			foreach ($params as $key => $param) {
				$request->addPostParameter($key, $param);
			}
			$request->addPostParameter('f', 'json');
			$response = $request->send();
			
			if (200 == $response->getStatus()) {
				$body = $response->getBody();
				if (preg_match('/^<\?xml/', $body)) {
					$xml = simplexml_load_string($body);
					$json = json_encode($xml);
					$array = json_decode($json, true);
					return $array;
				} else {
					return json_decode($body, true);
				}
			} else {
				return false;
			}
		} catch (HTTP_Request2_Exception $e) {
			return flase;
		}
	}
	
	/**
	 * @param $url
	 * @return bool|mixed
	 */
	private function get($url)
	{
		$request = new \HTTP_Request2($url);
		try {
			$request->setMethod(\HTTP_Request2::METHOD_GET);
			$response = $request->send();
			if (200 == $response->getStatus()) {
				return json_decode($response->getBody(), true);
			} else {
				return false;
			}
		} catch (HTTP_Request2_Exception $e) {
			return flase;
		}
	}
	
	
	public function logout()
	{
		if ($this->mode == self::MODE_HTTPS) {
			$response = $this->get('https://api.bme.jp/rest/1.0/authenticate/logout?access_token=' . $this->access_token . '&f=json');
			return true;
		} else {
			$params = [$this->access_token];
			$request = xmlrpc_encode_request('authenticate.logout', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$response = xmlrpc_decode($file);
			$this->access_token = false;
			$this->errors = [];
			return $response;
		}
	}
	
	private function makeContext($request, $method = 'POST', $header = "Content-Type: text/xml")
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
	
	public function addUserCustom($params){
		if ($this->access_token === false) {
			return false;
		}
		
		$params['access_token'] = $this->access_token; 
		if ($this->mode == self::MODE_HTTPS) {
			$response = $this->post('https://api.bme.jp/rest/1.0/contact/detail/create', $params);
			if (isset($response["contactID"])) {
				return (int)$response["contactID"];
			} else {
				return false;
			}
		}
	}
	
	public function addUser($email, $group)
	{
		if (empty($email) || empty($group) || $this->access_token === false) {
			return false;
		}
		
		if ($this->mode == self::MODE_HTTPS) {
			$response = $this->post('https://api.bme.jp/rest/1.0/contact/detail/create', [
				'access_token' => $this->access_token,
				'c15' => $email,
				'c21' => $group
			]);
			if (isset($response["contactID"])) {
				return (int)$response["contactID"];
			} else {
				return false;
			}
		} else {
			
			$params = [$this->access_token, ['c15' => $email, 'c21' => $group]];
			$request = xmlrpc_encode_request('contact.detailCreate', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			if ($this->checkError($data)) {
				return false;
			}
			return $data;
		}
	}
	
	public function findByEmail($email)
	{
		if (empty($email) || $this->access_token === false) {
			return false;
		}
		if ($this->mode == self::MODE_HTTPS) {
			$url = "https://api.bme.jp/rest/1.0/contact/detail/search?access_token={$this->access_token}&email=" . urlencode($email) . '&f=json';
			$response = $this->get($url);
			if (isset($response['contactID'])) {
				return (int)$response['contactID'];
			} else {
				return false;
			}
		} else {
			exit('not support.');
		}
	}
	
	public function delUser($id)
	{
		if ($this->mode == self::MODE_HTTPS) {
			//通常削除
			$response = $this->post("https://api.bme.jp/rest/1.0/contact/list/delete", [
				'access_token' => $this->access_token,
				'contactIDs' => $id
			]);
			//ゴミ箱から削除
			$response = $this->post("https://api.bme.jp/rest/1.0/contact/trash/delete", [
				'access_token' => $this->access_token,
				'contactIDs' => $id
			]);
			return true;
		} else {
			exit('not support.');
		}
	}
	
	const STATUS_HAISHIN = '配信中';
	const STATUS_TEISHI = '配信停止';
	const STATUS_KAIJO = '解除';
	const STATUS_SAKUJO = '削除';
	const STATUS_ERROR_TEISHI = 'エラー停止';
	
	public function changeStatus($id, $status)
	{
		if (
			self::STATUS_HAISHIN != $status &&
			self::STATUS_TEISHI != $status &&
			self::STATUS_KAIJO != $status &&
			self::STATUS_SAKUJO != $status &&
			self::STATUS_ERROR_TEISHI != $status
		) {
			return false;
		}
		
		if ($this->mode == self::MODE_HTTPS) {
			
			$response = $this->post('https://api.bme.jp/rest/1.0/contact/detail/update', [
				'access_token' => $this->access_token,
				'contactID' => (int)$id,
				'status' => $status
			]);
			if (isset($response->contactID)) {
				return (int)$response->contactID;
			} else {
				return false;
			}
		} else {
			$options = ['encoding' => 'UTF-8', 'escaping' => 'markup'];
			$params = [$this->access_token, (int)$id, ['status' => $status]];
			$request = xmlrpc_encode_request('contact.detailUpdate', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			if ($this->checkError($data)) {
				return false;
			}
			return $data;
		}
		
	}
	
	public function updateEmail($id, $email)
	{
		
		if ($this->mode == self::MODE_HTTPS) {
			
			$response = $this->post('https://api.bme.jp/rest/1.0/contact/detail/update', [
				'access_token' => $this->access_token,
				'contactID' => (int)$id,
				'c15' => $email
			]);
			if (isset($response['contactID'])) {
				return (int)$response['contactID'];
			} else {
				return false;
			}
		} else {
			
			exit('not support');
			
		}
		
	}
	
	
	private function checkError($data)
	{
		if ($data == -3) {
			$this->errors[] = ['code' => -3, 'message' => 'アドレス重複'];
			return true;
		} else if ($data == -2) {
			$this->errors[] = ['code' => -2, 'message' => '登録上限'];
			return true;
		} else if ($data == -1) {
			$this->errors[] = ['code' => -1, 'message' => 'パラメータ不正'];
			return true;
		} else if ($data == 0) {
			$this->errors[] = ['code' => 0, 'message' => '登録処理に失敗'];
			return true;
		}
		return false;
	}
	
	public function getErrors()
	{
		return $this->errors;
	}
	
	/*
	 * XMLRPMモードのみkeywordを配列で渡すと OR検索になる。2018.07現在
	 */
	public function search($opt = [])
	{
		
		$options = $opt + [
				'keywords' => [],
				'status' => '配信中',
				'order' => 'DESC',
				'page' => 1,
				'limit' => 20
			];
		
		if (
			!is_array($options['keywords']) ||
			!is_string($options['status']) ||
			!in_array(strtoupper($options['order']), ['ASC', 'DESC']) ||
			!is_numeric($options['page']) ||
			!is_numeric($options['limit'])
		) {
			return false;
		}
		
		
		$beginDate = "20110101T00:00:00";
		$endDate = date('Ymd') . "T23:59:59";
		$options['offset'] = ((int)$options['page'] - 1) * $options['limit'];
		
		if ($this->mode == self::MODE_HTTPS) {
			$url = "https://api.bme.jp/rest/1.0/contact/list/search?";
			$url .= "access_token=" . $this->access_token;
			$url .= "&keywords=" . urlencode(implode(' ', $options['keywords']));
			$url .= "&status=" . urlencode($options['status']);
			$url .= "&beginError=0&endError=10";
			$url .= "&beginDate=" . urlencode($beginDate);
			$url .= "&endDate=" . urlencode($endDate);
			$url .= "&orderBy=error&sortOrder=" . $options['order'];
			$url .= "&offset=" . $options['offset'];
			$url .= "&limit=" . $options['limit'];
			$url .= "&f=json";
			$result = $this->get($url);
			
			return $result['contacts'] ?? false;
			
		} else {
			xmlrpc_set_type($beginDate, 'datetime');
			xmlrpc_set_type($endDate, 'datetime');
			
			$params = [$this->access_token, $options['keywords'], $options['status'], [0, 10], [$beginDate, $endDate], 'error', $options['order'], $options['offset'], $options['limit']];
			$request = xmlrpc_encode_request('contact.listSearch', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			return $data;
		}
		
	}
	
	public function histoey($opt = [])
	{
		
		$options = $opt + [
				'page' => 1,
				'limit' => 20
			];
		
		if (
			!is_numeric($options['page']) ||
			!is_numeric($options['limit'])
		) {
			return false;
		}
		
		$beginDate = "20110101T00:00:00";
		$endDate = date('Ymd') . "T23:59:59";
		$options['offset'] = ((int)$options['page'] - 1) * $options['limit'];
		
		if ($this->mode == self::MODE_HTTPS) {
			
			$url = "https://api.bme.jp/rest/1.0/message/history/search?";
			$url .= "access_token=" . $this->access_token;
			$url .= "&subjects=";
			$url .= "&groups=";
			$url .= "&beginDate=" . urlencode($beginDate);
			$url .= "&endDate=" . urlencode($endDate);
			$url .= "&offset=" . $options['offset'];
			$url .= "&limit=" . $options['limit'];
			$url .= "&f=json";
			$result = $this->get($url);
			return $result['message'] ?? false;
			
		} else {
			xmlrpc_set_type($beginDate, 'datetime');
			xmlrpc_set_type($endDate, 'datetime');
			
			$params = [$this->access_token, [], [], [$beginDate, $endDate], $options['offset'], $options['limit']];
			$request = xmlrpc_encode_request('message.historySearch', $params, $options);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			return $data;
			
		}
		
		
	}
	
	public function reservation()
	{
		
		if ($this->mode == self::MODE_HTTPS) {
			
			$url = "https://api.bme.jp/rest/1.0/message/reservation/search?";
			$url .= "access_token=" . $this->access_token;
			$url .= "&f=json";
			$result = $this->get($url);
			return $result['message'] ?? false;
			
		} else {
			$params = [$this->access_token];
			$request = xmlrpc_encode_request('message.reservationSearch', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			print_r($data);
		}
		
	}
	
	
	public function getGroups()
	{
		
		if ($this->mode == self::MODE_HTTPS) {
			$response = $this->get('https://api.bme.jp/rest/1.0/group/list/search?access_token=' . $this->access_token . '&f=json');
			return $response['group'] ?? false;
		} else {
			$params = [$this->access_token];
			$request = xmlrpc_encode_request('group.listSearch', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			return $data;
		}
	}
	
	
	public function addMailReserve($scheduleDate, $senderID, $groupID, $subject, $body)
	{
		
		if (
			!$this->validateDateFormat($scheduleDate) ||
			!is_numeric($senderID) ||
			!is_numeric($groupID) ||
			!is_string($subject) || $subject == '' ||
			!is_string($body) || $body == ''
		) {
			return false;
		}
		
		if ($this->mode == self::MODE_HTTPS) {
			
			$response = $this->post('https://api.bme.jp/rest/1.0/message/schedule/create', [
				'access_token' => $this->access_token,
				'senderID' => $senderID,
				'groupID' => $groupID,
				'subject' => $subject,
				'textPart' => $body,
				'scheduleDate' => $scheduleDate
			]);
			return $response['messageID'] ?? false;
			
			
		} else {
			xmlrpc_set_type($scheduleDate, 'datetime');
			$params = [$this->access_token, ['senderID' => $senderID, 'groupID' => $groupID, 'subject' => $subject, 'textPart' => $body], $scheduleDate];
			$request = xmlrpc_encode_request('message.scheduleCreate', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			return $data;
		}
	}
	
	public function delMailReserve($id)
	{
		
		if ($this->mode == self::MODE_HTTPS) {
			$response = $this->post("https://api.bme.jp/rest/1.0/message/list/delete", [
				'access_token' => $this->access_token,
				'messageIDs' => $id
			]);
			
			if (!empty($response['success'])) {
				return (int)$response['success'];
			} else {
				return false;
			}
		} else {
			exit('not support.');
		}
	}
	
	
	public function validateDateFormat($datetime)
	{
		$time = strtotime($datetime);
		return $datetime === date('Ymd', $time) . 'T' . date('H:i:s', $time);
	}
	
	
	public function addMailNow($senderID, $groupID, $subject, $body)
	{
		
		if (
			!is_numeric($senderID) ||
			!is_numeric($groupID) ||
			!is_string($subject) || $subject == '' ||
			!is_string($body) || $body == ''
		) {
			return false;
		}
		
		
		if ($this->mode == self::MODE_HTTPS) {
			
			$response = $this->post('https://api.bme.jp/rest/1.0/message/sendnow/create', [
				'access_token' => $this->access_token,
				'senderID' => $senderID,
				'groupID' => $groupID,
				'subject' => $subject,
				'textPart' => $body,
			]);
			return $response['messageID'] ?? false;
			
		} else {
			$params = [$this->access_token, ['senderID' => $senderID, 'groupID' => $groupID, 'subject' => $subject, 'textPart' => $body]];
			$request = xmlrpc_encode_request('message.sendNowCreate', $params, self::REQUEST_OPTIONS);
			$context = $this->makeContext($request);
			$file = file_get_contents("https://api.bme.jp/xmlrpc/1.0", false, $context);
			$data = xmlrpc_decode($file);
			return $data;
		}
		
	}
	
	
}