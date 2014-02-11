<?
  class ValidationTest extends PHPUnit_Framework_TestCase
  {
    public function setUp()
    {
      $this->emailValidation = new EmailValidation();
    }

    public function testValidation()
    {
      $this->assertEquals(array('errorCode' => 2), $this->emailValidation->validate("lukasz.tlalk at gmail.com") );
     
      $this->assertEquals(array('errorCode' => 3,
                                'validatedEmail' => 'lukasz.tlalk@gmail.com'), $this->emailValidation->validate("lukasz.tlalk@gmial.com") );

      $this->assertEquals(array('errorCode' => 4), $this->emailValidation->validate("lukasz.tlalk@justrand123dd.com") );
      
      $this->assertEquals(array('errorCode' => 5), $this->emailValidation->validate("random@netblink.net") );

      $this->assertEquals(array('errorCode' => 6), $this->emailValidation->validate("lukasz.tlalka@mail-temporaire.fr") );
      
      $this->assertEquals(array('errorCode' => 1), $this->emailValidation->validate("lukasz.tlalka@netblink.net") );
    }

    public function testRFC()
    {
      $this->assertEquals(false, $this->emailValidation->validateRFC("lukasz.tlalka netblink.net") );
      $this->assertEquals(true, $this->emailValidation->validateRFC("lukasz.tlalka+testing@netblink.net") );
    }

    public function testDomainSpelling()
    {
      $this->assertEquals("lukasz.tlalka@gmail.com", $this->emailValidation->validateDomainSpelling("lukasz.tlalka@gamil.com") );
      $this->assertEquals("lukasz.tlalka@gmail.com", $this->emailValidation->validateDomainSpelling("lukasz.tlalka@gmail.com") );
    }

    public function testMX()
    {
      $this->assertEquals(false, $this->emailValidation->validateMX("lukasz.tlalka@g1amil.com") );
      $this->assertEquals(true, $this->emailValidation->validateMX("lukasz.tlalka@gmail.com") );
    }

    public function testDisposableEmail()
    {
      $this->assertEquals(true, $this->emailValidation->validateNonDisposableEmail("lukasz.tlalka@gmail.com") );
      $this->assertEquals(false, $this->emailValidation->validateNonDisposableEmail("lukasz.tlalka@mail-temporaire.fr") );
    }

    public function testIfAccountExistsOnTheMailServer()
    {
      $this->assertEquals(true, $this->emailValidation->validateAccountOnMailServer("lukasz.tlalka@netblink.net") );
      $this->assertEquals(false, $this->emailValidation->validateAccountOnMailServer("lukasz.tlalka2@netblink.net") );
    }
  }
