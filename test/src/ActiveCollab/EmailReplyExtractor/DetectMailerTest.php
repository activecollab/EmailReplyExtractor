<?php
  namespace ActiveCollab\EmailReplyExtractor\Test;

  use ActiveCollab\EmailReplyExtractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Test
   */
  class DetectMailerTest extends TestCase
  {
    /**
     * Test if we can detect Mail.app
     */
    public function testDetectAppleMail()
    {
      $this->assertEquals(EmailReplyExtractor::APPLE_MAIL, EmailReplyExtractor::detectMailer([ 'mime-version' => '1.0 (Apple Message framework v1278)' ]));
      $this->assertEquals(EmailReplyExtractor::APPLE_MAIL, EmailReplyExtractor::detectMailer([ 'x-mailer' => 'Apple Mail (2.1827)' ]));
    }

    /**
     * Test if we can detect Outlook desktop email
     */
    public function testDetectOutlook()
    {
      $this->assertEquals(EmailReplyExtractor::OUTLOOK, EmailReplyExtractor::detectMailer([ 'x-mailer' => 'Microsoft Office Outlook, Build 11.0.5510' ]));
      $this->assertEquals(EmailReplyExtractor::OUTLOOK, EmailReplyExtractor::detectMailer([ 'x-mailer' => 'Microsoft Office Outlook 12.0' ]));
      $this->assertEquals(EmailReplyExtractor::OUTLOOK, EmailReplyExtractor::detectMailer([ 'x-mailer' => 'Microsoft Outlook 14.0' ]));
      $this->assertEquals(EmailReplyExtractor::OUTLOOK, EmailReplyExtractor::detectMailer([ 'message-id' => '<597b57012a0f4f4c8952ed40f61ef9de@CO1PR07MB221.namprd07.prod.outlook.com>' ]));
    }

    /**
     * Test if we can detect Yahoo! webmail
     */
    public function testDetectYahoo()
    {
      $this->assertEquals(EmailReplyExtractor::YAHOO, EmailReplyExtractor::detectMailer([ 'x-mailer' => 'YahooMailWebService/0.8.170.612' ]));
    }

    /**
     * Test if we can detect Hushmail
     */
    public function testDetectHushmail()
    {
      $this->assertEquals(EmailReplyExtractor::HUSHMAIL, EmailReplyExtractor::detectMailer([ 'message-id' => "<20130319112326.1E7AD6F446@smtp.hushmail.com>" ]));
    }

    /**
     * Test if we can detect Hotmail
     */
    public function testDetectHotmail()
    {
      $this->assertEquals(EmailReplyExtractor::HOTMAIL, EmailReplyExtractor::detectMailer([ 'received' => "from BAY174-W36 ([65.54.190.187]) by bay0-omc3-s10.bay0.hotmail.com with Microsoft SMTPSVC(6.0.3790.4675);\n    Wed, 22 Jan 2014 18:02:13 -0800" ]));
    }
  }