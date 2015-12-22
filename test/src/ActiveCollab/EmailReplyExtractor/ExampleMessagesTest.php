<?php
  namespace ActiveCollab\EmailReplyExtractor\Test;

  use ActiveCollab\EmailReplyExtractor;
  use DirectoryIterator;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Test
   */
  class ExampleMessagesTest extends TestCase
  {
    /** @var string $expected_text */
    protected $expected_text;

    /**
     * Set up test case
     */
    public function setUp()
    {
      parent::setUp();
    }


    /**
     * Tear down test case
     */
    function tearDown() {
      parent::tearDown();
    }

    /**
     * Test example messages
     */
    public function testExampleMessages()
    {
      foreach (new DirectoryIterator(dirname(dirname(dirname(__DIR__))) . '/example_messages') as $file) {
        if ($file->isDot() || $file->isDir()) {
          continue;
        }

        if ($file->isFile() && $file->getExtension() == 'eml') {
          $expected_text = $this->setExpectedTextByFileName($file->getFilename());
          $this->assertEquals($expected_text, EmailReplyExtractor::extractReplyEml($file->getPathname()), $file->getFilename());
        }
      }
    }

    /**
     * Set expected text to test against
     *
     * @param string $filename
     * @return string
     */
    private function setExpectedTextByFileName($filename)
    {
      switch ($filename) {
        case 'apple_mail_case_01.eml':
          return 'Just an attachment';
        case 'yahoo_case_02.eml':
          return 'Reply koji uzrokuje da se u komentar uvoze i podaci o mail-u (task otvoren: https://afiveone.activecollab.net/projects/activecollab/tasks/2592)';
        case 'case_01.eml':
          return 'Šta ćemo sa ekipom koja je koristila lokalizaovani reply above this line?';
        case 'cyrilic_unicode_01.eml':
          return 'Ово ће бити ћирилични тест';
        case 'fw_gmail_to_m2p.eml':
          return 'apple->gmail->m2p';
        case 'fw_yahoo_to_m2p.eml':
          return 'gmail->yahoo->m2p';
        case 'latin_unicode_01.eml':
          return 'Šćućurih se u čaši povrh džačića.';
        case 'outlook_2013.eml':
          return 'Test from my Outlook.';
        case 'outlook_mac_2015.eml':
          return 'Pozdrav iz rudnika!';
        case 'unknown_case_01.eml':
          return 'Thanks Michael.  We will discuss and get back to you.';
        default:
          return 'Email Reply';
      }
    }
  }