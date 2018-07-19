<?php

namespace yamaaaaaa\Blayn\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use yamaaaaaa\Blayn\BlaynMail;

class BlaynMailTest extends TestCase
{
	
	private $bm;
	
	public function setUp()
	{
		$dotenv = new Dotenv(dirname(__DIR__));
		$dotenv->load();
		
	}
	
	private function login()
	{
		if (empty($this->bm)) {
			$this->bm = new BlaynMail(
				getenv('BLAYNMAIL_ID'),
				getenv('BLAYNMAIL_PASSWORD'),
				getenv('BLAYNMAIL_APIKEY')
			);
			$this->assertTrue(!empty($this->bm->getToken()));
		}
	}
	
	private function loginError()
	{
		
		$this->bm = new BlaynMail(
			getenv('BLAYNMAIL_ID'),
			getenv('BLAYNMAIL_PASSWORD'),
			'xxxxxxxxxxxxxxx'
		);
		$this->assertTrue($this->bm->getToken() == 0);
	}
	
	
	public function testConnect()
	{
		
		$this->loginError();
		
		$this->login();
		
		$this->assertTrue($this->bm->logout());
		
	}
	
	public function testAddUser()
	{
		
		$this->login();
		
		$email = getenv('BLAYNMAIL_EMAIL');
		$group = getenv('BLAYNMAIL_GROUP');
		
		$result = $this->bm->addUser($email, false);
		$this->assertFalse($result);
		
		$result = $this->bm->addUser('', $group);
		$this->assertFalse($result);
		
		$retult = $this->bm->addUser($email, $group);
		$this->assertTrue(is_numeric($retult));
	}
	
	
	public function testChangeStatus()
	{
		$this->login();
		$id = getenv('BLAYNMAIL_USER_ID');
		$email = getenv('BLAYNMAIL_EMAIL');
		$result = $this->bm->changeStatus($id, BlaynMail::STATUS_ERROR_TEISHI);
		$this->assertTrue(is_numeric($result));
	}
	
	public function testSearch()
	{
		
		$this->login();
		echo "\n";
		
		$result1 = $this->bm->search();
		$this->assertTrue(isset($result1[0]['contactID']));
		$this->assertTrue(isset($result1[0]['c15']));
//		echo print_r($result1[0],true);
		
		
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
			'keywords' => ['yamashiro', 'tamagusuku'], //OR検索になる
		]);
//		echo "<pre>" . print_r($result4, true) . "</pre>";
		$this->assertTrue(
			preg_match('/yamashiro/', $result4[0]['c15']) == 1 ||
			preg_match('/tamagusuku/', $result4[0]['c15']) == 1
		);
		
		$result5 = $this->bm->search([
			'keywords'=>'xxxxxxxxxxxxxxxxxxxldskfljsdifuwoeiruwer',
		]);
		$this->assertFalse($result5);
		
		
		$result6 = $this->bm->search([
			'keywords' => ['yamashiro'],
			'page' => 999
		]);
//		print_r($result6);
		$this->assertTrue($result6==[]);
		
		
	}
	
	
}
