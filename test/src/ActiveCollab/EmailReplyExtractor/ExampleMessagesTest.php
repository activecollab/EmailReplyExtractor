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

          $this->assertEquals($expected_text, EmailReplyExtractor::extractReplyEml($file->getPathname()));
          // assert that text of the stripped emails match the original text even with <br /> instead of new row char
          $this->assertEquals(nl2br($expected_text), nl2br(EmailReplyExtractor::extractReplyEml($file->getPathname())));
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
        case 'apple_mail_new_row.eml';
          // there is a space after 'novi red' in the original eml file as well.
          return "Daj neki,\nnovi red \n\nCisto da budemo sigurni\n\nOvaj je komentar je npr. poslat iz Apple Mail (Version 8.2 (2104))";
        case 'case_01.eml':
          return 'Šta ćemo sa ekipom koja je koristila lokalizaovani reply above this line?';
        case 'cyrilic_unicode_01.eml':
          return 'Ово ће бити ћирилични тест';
        case 'fw_gmail_to_m2p.eml':
          return 'apple->gmail->m2p';
        case 'fw_yahoo_to_m2p.eml':
          return 'gmail->yahoo->m2p';
        case 'gmail_new_row.eml':
          return "Ahoy,\nOvde sam opalio novi red.\n\nEvo još jedan prazan, i novi red.";
        case 'latin_unicode_01.eml':
          return 'Šćućurih se u čaši povrh džačića.';
        case 'outlook_2013.eml':
          return 'Test from my Outlook.';
        case 'outlook_mac_2015.eml':
          return 'Pozdrav iz rudnika!';
        case 'outlook_mac_2015_new_row.eml':
          return "Neki novi red.\nI paragraf?\n\nEvo ga jedan.\n\nEvo ga drugo.";
        case 'unknown_case_01.eml':
          return 'Thanks Michael.  We will discuss and get back to you.';
        case 'yahoo_case_02.eml':
          return 'Reply koji uzrokuje da se u komentar uvoze i podaci o mail-u (task otvoren: https://afiveone.activecollab.net/projects/activecollab/tasks/2592)';
        default:
          return 'Email Reply';
      }
    }
  }