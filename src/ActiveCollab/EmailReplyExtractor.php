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
    const APPLE_CLOUD_MAIL = 'AppleCloudMail';

    /**
     * Parse input file and return reply
     *
     * @param  string $path
     * @return string
     */
    public static function extractReplyEml($path)
    {
      $parser = new Parser();
      $parser->setPath($path);

      $extractor = self::getExtractorEml(self::detectMailer(self::getHeadersRelevantForMailerDetectionEml($parser)), $parser);

      return (string) $extractor->body;
    }

    /**
     * Parse input file and return reply
     *
     * @param  array  $headers
     * @param  string $body
     * @return string
     */
    public static function extractReply($headers, $body)
    {
      $extractor = self::getExtractor(self::detectMailer(self::getHeadersRelevantForMailerDetection($headers)), $body);

      return (string) $extractor->body;
    }

    /**
     * @param  string    $mailer
     * @param  Parser    $parser
     * @return Extractor
     */
    private function getExtractorEml($mailer, Parser &$parser)
    {
      $class_name = "ActiveCollab\\EmailReplyExtractor\\Extractor\\{$mailer}Extractor";

      return new $class_name(null, $parser);
    }

    /**
     * @param  string    $mailer
     * @param  string    $body
     * @return Extractor
     */
    private function getExtractor($mailer, $body)
    {
      $class_name = "ActiveCollab\\EmailReplyExtractor\\Extractor\\{$mailer}Extractor";

      return new $class_name($body);
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
        } else if (strpos($headers['message-id'], 'outlook.com') || strpos($headers['message-id'], 'phx.gbl')) {
          return self::OUTLOOK;
        } else if (strpos($headers['message-id'], 'yahoo.com')) {
          return self::YAHOO;
        } else if (strpos($headers['message-id'], 'me.com')) {
          return self::APPLE_CLOUD_MAIL;
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
    private static function getHeadersRelevantForMailerDetectionEml(Parser &$parser)
    {
      return self::filterHeaders([
        'x-mailer' => $parser->getHeader('x-mailer'),
        'message-id' => $parser->getHeader('message-id'),
        'Received' => $parser->getHeader('received'),
        'Mime-Version' => $parser->getHeader('mime-version'),
      ]);
    }

    /**
     * @param  array $headers
     * @return array
     */
    private static function getHeadersRelevantForMailerDetection(array $headers)
    {
      return self::filterHeaders([
        'x-mailer' => $headers['x-mailer'],
        'message-id' => $headers['message_id'],
        'Received' => $headers['Received'],
        'Mime-Version' => $headers['Mime-Version'],
      ]);
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

    /**
     * Return only not empty headers
     *
     * @param $headers
     *
     * @return mixed
     */
    private static function filterHeaders($headers)
    {
      foreach ($headers as $k => $v) {
        if (empty($v)) {
          unset($headers[$k]);
        }
      }

      return $headers;
    }
  }