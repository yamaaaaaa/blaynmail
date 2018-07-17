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
		$result = $this->bm->changeStatus($id,BlaynMail::STATUS_ERROR_TEISHI);
		$this->assertTrue(is_numeric($result));
	}
	
	
	
}
