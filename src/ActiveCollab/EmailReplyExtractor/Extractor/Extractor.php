<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  use ActiveCollab\EmailReplyExtractor;
  use eXorus\PhpMimeMailParser\Parser;

  /**
   * @package ActiveCollab\Extractor
   */
  abstract class Extractor
  {
    /**
     * @var Parser
     */
    private $parser;

    public function __construct($body = null, Parser $parser = null)
    {
      $this->parser = $parser;

      if($parser instanceof Parser) {
        if ($html = $this->parser->getMessageBody('html')) {
          $this->body = $this->toPlainText($html);
        } else {
          $this->body = $this->getParser()->getMessageBody('text');
        }
      } else {
        $this->body = $this->toPlainText($body);
      }

      $this->splitLines();
      $this->processLines();
      $this->joinLines();
    }

    /**
     * Prepare body text for processing
     */
    protected function splitLines()
    {
      $this->body = explode("\n", str_replace(["\n\r", "\r\n", "\r"], ["\n", "\n", "\n"], $this->body));
    }
    /**
     * Process body text
     */
    protected function processLines()
    {
      $splitters = $this->getAllMessageSplitters();

      if (!empty($splitters)) {
        $this->stripOriginalMessage($splitters);
      }

      $unwanted_text_patterns = $this->getUnwantedTextPatterns();

      if (!empty($unwanted_text_patterns)) {
        self::stripUnwantedText($unwanted_text_patterns);
      }

      self::stripSignature();
      self::convertPlainTextQuotesToBlockquotes();
    }

    /**
     * Join lines when done
     */
    public function joinLines()
    {
      // ltrim() strips BOM characters
      $this->body = ltrim(trim(implode("\n", $this->body)), "\xEF\xBB\xBF");
    }

      /**
       * Return both plain and Regex message splitters in the regex form
       *
       * @return array
       */
    protected function getAllMessageSplitters()
    {
        $plain_message_splitters = $this->getPlainMessageSplitters();
        return array_merge(
            array_map(function ($splitter) {
                return '/' . $splitter . '/';
            }, $plain_message_splitters),
            $this->getRegexMessageSplitters()
        );
    }

      /**
       * Return original message splitters
       *
       * @return array
       */
      protected function getPlainMessageSplitters()
      {
          return [
//        '----- Forwarded Message -----',
              '- Reply above this line to leave a comment -',
              '-- REPLY ABOVE THIS LINE --',
              '-- REPLY ABOVE THIS LINE',
              'REPLY ABOVE THIS LINE --',
              '-- Reply above this line --',
              '-----Original Message-----',
              '----- Original Message -----',
              '-- ODGOVORI ODJE --',
              '-------- Original message --------',
          ];
      }

      /**
       * Return regex message splitters
       *
       * @return array
       */
      protected function getRegexMessageSplitters()
      {
          return [
              '/On(.*?)wrote\:(.*?)/is',
              '/^Am(.*?)schrieb(.*?)/is',
          ];
      }

    /**
     * Chack if string is regular expression
     *
     * @param $str
     *
     * @return bool
     */
    protected function isRegex($str)
    {
      try {
        preg_match($str, 'some str');
      } catch (\Exception $e) {
        return false;
      }

      return true;
    }

    /**
     * Find reply separator and remove lines that are after it
     *
     * @param  array   $splitters
     * @param  integer $trim_previous_lines
     */
    protected function stripOriginalMessage(array &$splitters, $trim_previous_lines = 0) {
      $stripped = [];

      foreach ($this->body as $line) {
        foreach ($splitters as $splitter) {
          if (preg_match($splitter, $line)) {
            if ($trim_previous_lines == 0) {
              $this->body = $stripped;
            } else {
              $this->body = array_slice($stripped, 0, count($stripped) - $trim_previous_lines);
            }

            $this->stripEmptyLinesFromTheEnd();
            return;
          }
        }
        $stripped[] = $line;
      }

    }

    /**
     * Remove empty or quote lines from the end of the mail
     */
    protected function stripEmptyLinesFromTheEnd()
    {
      for ($i = count($this->body) - 1; $i >= 0; $i--) {
        $line = trim($this->body[$i]);

        if (empty($line) || in_array($line, [ '>', '&gt;', '> **', '&gt; **' ])) {
          unset($this->body[$i]);
        } else {
          break;
        }
      }
    }

    /**
     * @return array
     */
    protected function getUnwantedTextPatterns()
    {
      return [
        '/^On(.*?)wrote:(.*?)/is',
        '/^Am(.*?)schrieb(.*?)/is',
        '/^(.*?)\/(.*?)\/(.*?)\<(.*?)\>(.*?)/is',
        '/^(.*?)\/(.*?)\/(.*?)&lt;(.*?)&gt;(.*?)/is'
      ];
    }

    /**
     * Strip unwanted text after stripping reply
     *
     * @param array $unwanted_text_patterns
     */
    function stripUnwantedText(array $unwanted_text_patterns) {
      foreach ($unwanted_text_patterns as $unwanted_text_pattern) {
        list ($unwanted_text, $cut_line) = self::getLinesFromEnd($this->body, 1, true);
        $unwanted_text = trim(implode('', $unwanted_text));
        if (preg_match($unwanted_text_pattern, $unwanted_text)) {
          $this->body = array_splice($this->body, 0, $cut_line);
          return;
        }

        list ($unwanted_text, $cut_line) = self::getLinesFromEnd($this->body, 2, true);
        $unwanted_text = trim(implode('', $unwanted_text));
        if (preg_match($unwanted_text_pattern, $unwanted_text)) {
          $this->body = array_splice($this->body, 0, $cut_line);
          return;
        }
      }

      $body = implode("\n", $this->body);
      if (preg_match('/(.*)(From\:)(.*)\nTo\:(.*)\nSent\:(.*)\nSubject\:(.*)/mis', $body, $matches, PREG_OFFSET_CAPTURE) || preg_match('/(.*)(From\:)(.*)\nSent\:(.*)\nTo\:(.*)\nSubject\:(.*)/mis', $body, $matches, PREG_OFFSET_CAPTURE)) {
        $match_index = $matches[2][1];
        $body = trim(mb_substr($body, 0, $match_index));
        $this->body = explode("\n", $body);
      }

      return;
    }

    /**
     * Strip signature from email
     */
    function stripSignature() {
      for ($x = 0, $lines_count = count($this->body); $x < $lines_count; $x++) {
        $line = trim($this->body[(($lines_count - $x) - 1)]);
        if ($line && trim($line)) {
          if ($line == "-- " || $line == "--") {
            $this->body = array_splice($this->body, 0, (($lines_count - $x) - 1));
            return;
          }

          // Should signature be longer than 8 lines?
          if ($x > 8) {
            return;
          }
        }
      }
    }

    /**
     * Get lines from end
     *
     * @param  integer $number_of_lines
     * @param  boolean $empty_breaks
     * @return array
     */
    protected function getLinesFromEnd($number_of_lines = 1, $empty_breaks = false) {
      $lines_found = [];
      $target_line = 0;

      for($x = 0, $lines_count = count($this->body); $x < $lines_count; $x++) {
        $line = trim($this->body[$lines_count - $x - 1]);
        if ($line) {
          $lines_found = array_merge((array) $line, $lines_found);
          if (count($lines_found) == $number_of_lines) {
            $target_line = $lines_count - $x - 1;
            break;
          }
        } else {
          if (count($lines_found) && $empty_breaks) {
            $target_line = $lines_count - $x - 1;
            break;
          }
        }
      }

      return [ $lines_found, $target_line ];
    }

    /**
     * Converts plaintext quotes to blockquotes
     */
    function convertPlainTextQuotesToBlockquotes() {
      $block_quote_opened = false;
      $lines = [];

      for ($x = 0, $lines_count = count($this->body); $x < $lines_count; $x++) {
        $line = $this->body[$x];

        if (mb_substr($line, 0, 1) == '>' || mb_substr($line, 0, 4) == '&gt;') {
          if (!$block_quote_opened) {
            $lines[] = "<blockquote>\n";
            $block_quote_opened = true;
          }
        } else {
          if ($block_quote_opened) {
            $lines[] = "</blockquote>\n";
            $block_quote_opened = false;
          }
        }

        if (mb_substr($line,0,1) == '>') {
          $lines[] = mb_substr($line, 1);
        } else if (mb_substr($line,0,4) == '&gt;') {
          $lines[] = mb_substr($line, 4);
        } else {
          $lines[] = $line;
        }
      }

      if ($block_quote_opened) {
        $lines[] = "</blockquote>";
      }

      $this->body = $lines;
    }

    /**
     * @return Parser
     */
    protected function &getParser()
    {
      return $this->parser;
    }

    /**
     * @param  string $html
     * @return string
     */
    static function toPlainText($html) {
      $plain = (string) $html;


      // strip slashes
      $plain = (string) trim(stripslashes($plain));

      // strip unnecessary characters
      $plain = (string) preg_replace([
        "/\r/", // strip carriage returns
        "/<script[^>]*>.*?<\/script>/si", // strip immediately, because we don't need any data from it
        "/<style[^>]*>.*?<\/style>/is", // strip immediately, because we don't need any data from it
        "/style=\".*?\"/"   //was: '/style=\"[^\"]*/'
      ], "", $plain);

      // entities to convert (this is not a definite list)
      $entities = [
        ' '     => [ '&nbsp;', '&#160;' ],
        '"'     => [ '&quot;', '&rdquo;', '&ldquo;', '&#8220;', '&#8221;', '&#147;', '&#148;' ],
        '\''    => [ '&apos;', '&rsquo;', '&lsquo;', '&#8216;', '&#8217;' ],
        '>'     => [ '&gt;' ],
        '<'     => [ '&lt;' ],
        '&'     => [ '&amp;', '&#38;' ],
        '(c)'   => [ '&copy;', '&#169;' ],
        '(R)'   => [ '&reg;', '&#174;' ],
        '(tm)'  => [ '&trade;', '&#8482;', '&#153;' ],
        '--'    => [ '&mdash;', '&#151;', '&#8212;' ],
        '-'     => [ '&ndash;', '&minus;', '&#8211;', '&#8722;' ],
        '*'     => [ '&bull;', '&#149;', '&#8226;' ],
        '�'     => [ '&pound;', '&#163;' ],
        'EUR'   => [ '&euro;', '&#8364;' ]
      ];

      // convert specified entities
      foreach ($entities as $character => $entity) {
        $plain = (string) str_replace($entity, $character, $plain);
      }

      // strip other not previously converted entities
      $plain = (string) preg_replace([ '/&[^&;]+;/si' ], "", $plain);

      // <p> converts to 2 newlines
      $plain = (string) preg_replace('/<p[^>]*>/i', "\n\n", $plain); // <p>

      // new line after div
      $plain = (string) preg_replace('/<div[^>]*>/i', "\n", $plain); // <div>

      // uppercase html elements
      $plain = (string) preg_replace_callback('/<h[123456][^>]*>(.*?)<\/h[123456]>/i', function($matches) {
        return "\n\n" . mb_strtoupper($matches[1]) . "\n\n";
      }, $plain); // <h1-h6>

      $plain = (string) preg_replace_callback([ '/<b[^>]*>(.*?)<\/b>/i', '/<strong[^>]*>(.*?)<\/strong>/i' ], function($matches) {
        return $matches[1];
      }, $plain); // <b> <strong>

      // deal with italic elements
      $plain = (string) preg_replace(array('/<i[^>]*>(.*?)<\/i>/i', '/<em[^>]*>(.*?)<\/em>/i'), '_\\1_', $plain); // <i> <em>

      // elements that convert to 2 newlines
      $plain = (string) preg_replace(array('/(<ul[^>]*>|<\/ul>)/i', '/(<ol[^>]*>|<\/ol>)/i', '/(<table[^>]*>|<\/table>)/i'), "\n\n", $plain); // <ul> <ol> <table>

      // elements that convert to single newline
      $plain = (string) preg_replace(array('/<br[^>]*>/i', '/(<tr[^>]*>|<\/tr>)/i'), "\n", $plain); // <br> <tr>

      // images
      $plain = (string) preg_replace(array('/<img\s+[^>]*src="([^"]*)"[^>]*>/i'), "[Image: \\1]", $plain); // <br> <tr>

      // <hr> converts to --------------------//---
      $plain = (string) preg_replace('/<hr[^>]*>/i', "\n-------------------------\n", $plain); // <hr>

      // other table tags
      $plain = (string) preg_replace('/<td[^>]*>(.*?)<\/td>/i', "\t\\1\n", $plain); // <td>
      $plain = (string) preg_replace_callback('/<th[^>]*>(.*?)<\/th>/i', function($matches) {
        return "\t\t" . mb_strtoupper($matches[0]) . "\n";
      }, $plain); // <th>

      // list elements
      $plain = (string) preg_replace('/<li[^>]*>(.*?)<\/li>/i', "* \\1\n", $plain); // <li>with content</li>
      $plain = (string) preg_replace('/<li[^>]*>/i', "\n* ", $plain); // <li />

      // handle anchors
      $plain = (string) preg_replace_callback('/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i', function($matches) {
        $url = $matches[1];
        $text = $matches[2];

        if (EmailReplyExtractor::strStartsWith($url, 'http://') || EmailReplyExtractor::strStartsWith($url, 'https://')) {
          return "$text [$url]";
        } else if (EmailReplyExtractor::strStartsWith($url, 'mailto:')) {
          return $text . ' [' . substr($url, 7) . ']';
        } else {
          return $text;
        }
      }, $plain); // <a href="$url">$text</a>

      // handle blockquotes
      $plain = (string) preg_replace_callback('/<blockquote[^>]*>(.*?)<\/blockquote>/is', function ($blockquote_content) {
        $blockquote_content = isset($blockquote_content[1]) ? $blockquote_content[1] : '';

        $lines = (array) explode("\n", $blockquote_content);
        $return = array();
        if (!empty($lines)) {
          foreach ($lines as $line) {
            $return[] = '> ' . $line;
          }
        }
        return "\n\n" . implode("\n", $return) . "\n\n";
      }, $plain);

      $plain = (string) preg_replace('/<title[^>]*>(.*?)<\/title>/i', "", $plain); // remove unnecessary title tag

      // strip other tags
      $plain = (string) strip_tags($plain);

      // clean up unnecessary newlines
      $plain = (string) preg_replace("/\n\s+\n/", "\n\n", $plain);
      $plain = (string) preg_replace("/[\n]{3,}/", "\n\n", $plain);

      return trim($plain);
    }
  }
