<?php
namespace takamoriyamashiro\Blayn\Tests;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use takamoriyamashiro\Blayn\BlaynMail;

class BlaynMailTest extends TestCase{
	
	private $bm;

	public function setUp()
    {
	    $dotenv = new Dotenv(dirname(__DIR__));
	    $dotenv->load();
	    
    }
    
    private function login(){
	
	    if(empty($this->bm)) {
		    $this->bm = new BlaynMail(
			    getenv('BRAYNMAIL_ID'),
			    getenv('BRAYNMAIL_PASSWORD'),
			    getenv('BRAYNMAIL_APIKEY')
		    );
		    $this->assertTrue(!empty($this->bm->getToken()));
	    }
    }

    public function testConnect(){
	
	    $this->bm = new BlaynMail(
		    getenv('BRAYNMAIL_ID'),
		    getenv('BRAYNMAIL_PASSWORD'),
		    'xxxxxxxxxxxxxxx'
	    );
	    $this->assertTrue($this->bm->getToken() == 0);
	
	    $this->login();
	
	    $this->assertTrue($this->bm->logout());
	
    }
    
    public function testAddUser(){
	
	    $this->login();
	
	    $email = 'yamashiro.develop@gmail.com';
	    $this->bm->addUser(
		    $email,
	    );
	    
//	    $this->assertTrue(true);
    	
    	
    }

    
    
}
