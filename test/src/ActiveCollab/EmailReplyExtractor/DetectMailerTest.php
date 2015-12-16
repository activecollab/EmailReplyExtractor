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
     * Test if we can detect MailRu
     */
    public function testDetectMailRu()
    {
      $this->assertEquals(EmailReplyExtractor::MAIL_RU_MAIL, EmailReplyExtractor::detectMailer([ 'message-id' => '<1448892807.548119671@f238.i.mail.ru>' ]));
    }
  }