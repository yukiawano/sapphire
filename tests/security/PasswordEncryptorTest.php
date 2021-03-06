<?php
class PasswordEncryptorTest extends SapphireTest {

	/**
	 *
	 * @var Config
	 */
	private $config = null;

	public function setUp() {
		parent::setUp();
		$this->config = clone(Config::inst());
	}

	public function tearDown() {
		parent::tearDown();
		Config::set_instance($this->config);
	}

	function testCreateForCode() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test'=>array('PasswordEncryptorTest_TestEncryptor'=>null)));
		$e = PasswordEncryptor::create_for_algorithm('test');
		$this->assertInstanceOf('PasswordEncryptorTest_TestEncryptor', $e );
	}
	
	/**
	 * @expectedException PasswordEncryptor_NotFoundException
	 */
	function testCreateForCodeNotFound() {
		PasswordEncryptor::create_for_algorithm('unknown');
	}
	
	function testRegister() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test'=>array('PasswordEncryptorTest_TestEncryptor'=>null)));
		$encryptors = PasswordEncryptor::get_encryptors();
		$this->assertContains('test', array_keys($encryptors));
		$encryptor = $encryptors['test'];
		$this->assertContains('PasswordEncryptorTest_TestEncryptor', key($encryptor));
	}
	
	function testUnregister() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test'=>array('PasswordEncryptorTest_TestEncryptor'=>null)));
		Config::inst()->remove('PasswordEncryptor', 'encryptors', 'test');
		$this->assertNotContains('test', array_keys(PasswordEncryptor::get_encryptors()));
	}
	
	function testEncryptorPHPHashWithArguments() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test_md5'=>array('PasswordEncryptor_PHPHash'=>'md5')));
		$e = PasswordEncryptor::create_for_algorithm('test_md5');
		$this->assertEquals('md5', $e->getAlgorithm());
	}
	
	function testEncryptorPHPHash() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test_sha1'=>array('PasswordEncryptor_PHPHash'=>'sha1')));
		$e = PasswordEncryptor::create_for_algorithm('test_sha1');
		$password = 'mypassword';
		$salt = 'mysalt';
		$this->assertEquals(
			hash('sha1', $password . $salt), 
			$e->encrypt($password, $salt)
		);
	}

	function testEncryptorBlowfish() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test_blowfish'=>array('PasswordEncryptor_Blowfish'=>'')));
		$e = PasswordEncryptor::create_for_algorithm('test_blowfish');
		$password = 'mypassword';
		$salt = '10$mysaltmustbetwen2chars';

		$this->assertTrue($e->checkAEncryptionLevel() == 'y' || $e->checkAEncryptionLevel() == 'x' || $e->checkAEncryptionLevel() == 'a');
		$this->assertTrue($e->check($e->encrypt($password, $salt), "mypassword", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "anotherpw", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "mypassword", '10$anothersaltetwen2chars'));
	}
	
	function testEncryptorPHPHashCheck() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test_sha1'=>array('PasswordEncryptor_PHPHash'=>'sha1')));
		$e = PasswordEncryptor::create_for_algorithm('test_sha1');
		$this->assertTrue($e->check(sha1('mypassword'), 'mypassword'));
		$this->assertFalse($e->check(sha1('mypassword'), 'mywrongpassword'));
	}
	
	/**
	 * See http://open.silverstripe.org/ticket/3004
	 * 
	 * Handy command for reproducing via CLI on different architectures:
	 * 	php -r "echo(base_convert(sha1('mypassword'), 16, 36));"
	 */
	function testEncryptorLegacyPHPHashCheck() {
		Config::inst()->update('PasswordEncryptor', 'encryptors', array('test_sha1legacy'=>array('PasswordEncryptor_LegacyPHPHash'=>'sha1')));
		$e = PasswordEncryptor::create_for_algorithm('test_sha1legacy');
		// precomputed hashes for 'mypassword' from different architectures
		$amdHash = 'h1fj0a6m4o6k0sosks88oo08ko4gc4s';
		$intelHash = 'h1fj0a6m4o0g04ocg00o4kwoc4wowws';
		$wrongHash = 'h1fjxxxxxxxxxxxxxxxxxxxxxxxxxxx';
		$this->assertTrue($e->check($amdHash, "mypassword"));
		$this->assertTrue($e->check($intelHash, "mypassword"));
		$this->assertFalse($e->check($wrongHash, "mypassword"));
	}
}

class PasswordEncryptorTest_TestEncryptor extends PasswordEncryptor implements TestOnly {
	function encrypt($password, $salt = null, $member = null) {
		return 'password';
	}
	
	function salt($password, $member = null) {
		return 'salt';
	}
}
