<?php

namespace yamaaaaaa\Blayn\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use yamaaaaaa\Blayn\BlaynMail;

class BlaynMailTest extends TestCase
{
	
	private $bm;
	
	public function setUp(): void
	{
		date_default_timezone_set('Asia/Tokyo');
		$dotenv = Dotenv::createImmutable(dirname(__DIR__) . '/');
		$dotenv->load();
	}
	
	private function login()
	{
		if (empty($this->bm)) {
			$this->bm = new BlaynMail(
					$_ENV['BLAYNMAIL_ID'],
					$_ENV['BLAYNMAIL_PASSWORD'],
					$_ENV['BLAYNMAIL_APIKEY']
			);
			$this->assertTrue(!empty($this->bm->getToken()));
		}
	}
	
	private function loginError()
	{
		
		$this->bm = new BlaynMail(
				$_ENV['BLAYNMAIL_ID'],
				$_ENV['BLAYNMAIL_PASSWORD'],
				'xxxxxxxxxxxxxxx'
		);
		$this->assertTrue($this->bm->getToken() == 0);
	}
	
	
	public function testConnect()
	{
//		$this->loginError();
//
		$this->login();
		$this->assertTrue($this->bm->logout());
		
	}
	
	
	public function testFindByEmail()
	{
		
		$this->login();
		$email = $_ENV['BLAYNMAIL_EMAIL'];
		
		echo "\n";
		echo $email . "\n";
		
		$result = $this->bm->findByEmail($email);
		$this->assertTrue(is_numeric($result));
		var_dump($result);
		echo 'ID:' . $result . "\n";
		
		
	}
	
	public function testUpdateEmail()
	{
		
		$this->login();
		$email = $_ENV['BLAYNMAIL_EMAIL'];
		$id = $_ENV['BLAYNMAIL_USER_ID'];
		
		echo "\n";
		echo $id . "\n";
		echo $email . "\n";
		
		$result = $this->bm->updateEmail($id, $email);
		var_dump($result);
		$this->assertTrue(is_numeric($result));
		echo 'ID:' . $result . "\n";
		
	}
	
	public function testAddUser()
	{
		
		$this->login();
		
		$email = $_ENV['BLAYNMAIL_EMAIL'];
		$group = $_ENV['BLAYNMAIL_GROUP'];
		
		$result = $this->bm->addUser($email, false);
		$this->assertFalse($result);
		
		$result = $this->bm->addUser('', $group);
		$this->assertFalse($result);
		
		$retult = $this->bm->addUser($email, $group);
		echo $retult;
		$this->assertTrue(is_numeric($retult));
	}
	public function testAddUserCustom()
	{
		
		$this->login();
		
		//keyは契約に合わせてまちまち
		$params = [
				$_ENV['BLAYNMAIL_EMAIL_CODE'] => '02test@yama-lab.com',
				$_ENV['BLAYNMAIL_NAME_CODE'] => 'テスト002',
				$_ENV['BLAYNMAIL_AGE_CODE'] => '20代',
				$_ENV['BLAYNMAIL_LOCATION_CODE'] => '宜野湾市',
		];
		
//		echo "<pre>".print_r($params,true)."</pre>";exit;
		
		$result = $this->bm->addUserCustom($params);
		echo $retult;exit;
		$this->assertTrue(is_numeric($retult));
	}
	
	public function testDelUser()
	{
		$this->login();
		$email = $_ENV['BLAYNMAIL_DEL_EMAIL'];
		$this->assertEquals($email, '01test@yama-lab.com');
		$id = $this->bm->findByEmail($email);
		$result = $this->bm->delUser($id);
		$this->assertTrue($result);
	}
	
	
	public function testChangeStatus()
	{
		$this->login();
		$id = $_ENV['BLAYNMAIL_USER_ID'];
		$email = $_ENV['BLAYNMAIL_EMAIL'];
		$result = $this->bm->changeStatus($id, BlaynMail::STATUS_HAISHIN);
		$this->assertTrue(is_numeric($result));
	}
	
	public function testSearch()
	{
		
		$this->login();
		echo "\n";
		
		$result1 = $this->bm->search();
//		var_dump($result1[0]);
		$this->assertTrue(isset($result1[0]['contactID']));
		$this->assertTrue(isset($result1[0]['c15']));
//		echo print_r($result1[0],true);
//		return;
//		
		
		$result2 = $this->bm->search([
				'order' => 'ASC',
		]);
		$this->assertTrue($result1[0]['c15'] != $result2[0]['c15']);
//		echo print_r($result2[0],true);
		
		$result3 = $this->bm->search([
				'keywords' => ['oka'],
				'limit' => 1
		]);
//		echo print_r($result3[0],true);
		$this->assertTrue(count($result3) == 1);
		$this->assertTrue(preg_match('/oka/', $result3[0]['c15']) == 1);
		
		
		$result4 = $this->bm->search([
				'keywords' => ['test04'],
		]);
		echo "<pre>" . print_r($result4, true) . "</pre>";
		$this->assertTrue(
				preg_match('/test04/', $result4[0]['c15']) == 1
		);
		
		$result5 = $this->bm->search([
				'keywords' => 'xxxxxxxxxxxxxxxxxxxldskfljsdifuwoeiruwer',
		]);
		$this->assertFalse($result5);
		
		
		$result6 = $this->bm->search([
				'keywords' => ['yamashiro'],
				'page' => 999
		]);
//		print_r($result6);
		$this->assertTrue($result6 == []);
		
		
	}
	
	public function testGroups()
	{
		
		$this->login();
		$result = $this->bm->getGroups();
		$this->assertTrue(is_array($result));
		$this->assertTrue(count($result) > 0);
		
	}
	
	public function testHistory()
	{
		
		$this->login();
		
		$result = $this->bm->histoey([
				'limit' => 5
		]);
		foreach ($result as $key => $mail)
			echo "\n" . $mail['subject'];
		$this->assertTrue(count($result) > 0);
		
		$result = $this->bm->histoey([
				'page' => 100
		]);
		echo "\n-----------------------------------------------\n";
		
		$this->assertTrue($result == []);
	}
	
	public function testReservation()
	{
		$this->login();
		
		$result = $this->bm->reservation();
		foreach ($result as $key => $mail)
			echo "\n" . $mail['subject'];
		
		$this->assertTrue(is_array($result));
	}
	
	public function testAddMailReserve()
	{
		
		echo "\n";
		
		$this->login();
		$time = time() + (60 * 60);//1時間後
		$scheduleDate = date('Ymd', $time) . 'T' . date('H:i:s', $time);
		$senderID = (int)$_ENV['BLAYNMAIL_SENDER_ID'];
		$groupID = (int)$_ENV['BLAYNMAIL_GROUP_ID'];
		$subject = '【テスト配信】これはテスト配信です。(返信不要)';
		$body = "これはテスト配信です。\n返信不要ですのでそのまま破棄して頂ますようよろしくお願い致します。";
		
		$result = $this->bm->addMailReserve($scheduleDate, $senderID, $groupID, $subject, $body);
		var_dump($result);
		$this->assertTrue(is_numeric($result));
		
	}
	
	public function testDelMailReserve()
	{
		
		echo "\n";
		$this->login();
		$id = $_ENV['BLAYNMAIL_DEL_MESSAGE_ID'];
		$result = $this->bm->delMailReserve($id);
		$this->assertTrue(is_numeric($result));
		
	}
	
	
	public function testAddMailNow()
	{
		echo "\n";
		
		$this->login();
		$senderID = (int)$_ENV['BLAYNMAIL_SENDER_ID'];
		$groupID = (int)$_ENV['BLAYNMAIL_GROUP_ID'];
		$subject = '【テスト配信】これはテスト配信です。(返信不要)';
		$body = "これはテスト配信です。\n返信不要ですのでそのまま破棄して頂ますようよろしくお願い致します。";
		
		$result = $this->bm->addMailNow($senderID, $groupID, $subject, $body);
		var_dump($result);
		$this->assertTrue(is_numeric($result));
		
		
	}
	
	
}
