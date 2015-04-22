<?php
  namespace ActiveCollab;

  use eXorus\PhpMimeMailParser\Parser, ActiveCollab\EmailReplyExtractor\Extractor\Extractor;

  /**
   * @package ActiveCollab
   */
  final class EmailReplyExtractor
  {
    const APPLE_MAIL = 'AppleMail';
    const GENERIC = 'Generic';
    const GOOGLE_MAIL = 'GoogleMail';
    const HOTMAIL = 'Hotmail';
    const HUSHMAIL = 'Hushmail';
    const IOS = 'iOS';
    const OUTLOOK = 'Outlook';
    const YAHOO = 'Yahoo';

    /**
     * Parse input file and return reply
     *
     * @param  string $path
     * @return string
     */
    public static function extractReply($path)
    {
      $parser = new Parser();
      $parser->setPath($path);

      $extractor = self::getExtractor(self::detectMailer(self::getHeadersRelevantForMailerDetection($parser)), $parser);

      return (string) $extractor;
    }

    /**
     * @param  string    $mailer
     * @param  Parser    $parser
     * @return Extractor
     */
    private function getExtractor($mailer, Parser &$parser)
    {

      $class_name = "ActiveCollab\\EmailReplyExtractor\\Extractor\\{$mailer}Extractor";

      return new $class_name($parser);
    }

    /**
     * Check headers and try to detect mailer
     *
     * @param  array       $headers
     * @return bool|string
     */
    public static function detectMailer(array $headers)
    {
      if (isset($headers['x-mailer'])) {
        if (strpos($headers['x-mailer'], 'iPod Mail') !== false || strpos($headers['x-mailer'], 'iPad Mail') !== false || strpos($headers['x-mailer'], 'iPhone Mail') !== false) {
          return self::IOS;
        } else if (strpos($headers['x-mailer'], 'Microsoft Office Outlook') !== false || strpos($headers['x-mailer'], 'Microsoft Outlook 14.') !== false || strpos($headers['x-mailer'], 'Microsoft Windows Live Mail') !== false) {
          return self::OUTLOOK;
        } else if (strpos($headers['x-mailer'], 'YahooMail') !== false) {
          return self::YAHOO;
        } else if (strpos($headers['x-mailer'], 'Apple Mail') !== false) {
          return self::APPLE_MAIL;
        }
      } else if (isset($headers['message-id'])) {
        if (strpos($headers['message-id'], '@email.android.com') !== false || strpos($headers['message-id'], '@mail.gmail.com') !== false) {
          return self::GOOGLE_MAIL;
        } else if (strpos($headers['message-id'], '@smtp.hushmail.com')) {
          return self::HUSHMAIL;
        } else if (strpos($headers['message-id'], 'outlook.com')) {
          return self::OUTLOOK;
        }
      } else if (isset($headers['received']) && strpos($headers['received'], 'hotmail.com') !== false) {
        return self::HOTMAIL;
      } else if (isset($headers['mime-version']) && strpos($headers['mime-version'], 'Apple Message framework') !== false) {
        return self::APPLE_MAIL;
      }

      return self::GENERIC;
    }

    /**
     * @param  Parser $parser
     * @return array
     */
    private static function getHeadersRelevantForMailerDetection(Parser &$parser)
    {
      $headers = [
        'x-mailer' => $parser->getHeader('x-mailer'),
        'message-id' => $parser->getHeader('message-id'),
        'Received' => $parser->getHeader('received'),
        'Mime-Version' => $parser->getHeader('mime-version'),
      ];

      foreach ($headers as $k => $v) {
        if (empty($v)) {
          unset($headers[$k]);
        }
      }

      return $headers;
    }

    /**
     * This function will return true only if input string starts with
     * niddle
     *
     * @param  string  $string
     * @param  string  $niddle
     * @return boolean
     */
    public static function strStartsWith($string, $niddle) {
      return substr($string, 0, strlen($niddle)) == $niddle;
    }
  }