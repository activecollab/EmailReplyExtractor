<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  use ActiveCollab\EmailReplyExtractor;
  use eXorus\PhpMimeMailParser\Parser, HTML_To_Markdown;

  /**
   * @package ActiveCollab\Extractor
   */
  abstract class Extractor
  {
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
      $this->parser = $parser;

      if ($html = $this->parser->getMessageBody('html')) {
        $html_to_markdown = new HTML_To_Markdown();
        $html_to_markdown->convert($html);

        $this->body = $this->toPlainText($html);
      } else {
        $this->body = $this->getParser()->getMessageBody('text');
      }

      $this->splitLines();
      $this->processLines();
      $this->joinLines();
    }

    /**
     * @var string|string[]
     */
    protected $body;

    /**
     * @return string
     */
    public function __toString()
    {
      return $this->body;
    }

    /**
     * Prepare body text for processing
     */
    protected function splitLines()
    {
      $this->body = explode("\n", str_replace([ "\n\r", "\r\n", "\r" ], [ "\n", "\n", "\n" ], $this->body));
    }

    /**
     * Process body text
     */
    protected function processLines()
    {
      $splitters = $this->getOriginalMessageSplitters();

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
      $this->body = trim(implode("\n", $this->body));
    }

    /**
     * Return splitters
     *
     * @return array
     */
    protected function getOriginalMessageSplitters()
    {
      return [
        '-- REPLY ABOVE THIS LINE --',
        '-- REPLY ABOVE THIS LINE',
        '-- Reply above this line --',
        '-----Original Message-----',
        '-------- Original message --------'
      ];
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
          if (mb_strpos($line, $splitter) !== false) {
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
        $unwanted_text = trim(implode(null, $unwanted_text));
        if (preg_match($unwanted_text_pattern, $unwanted_text)) {
          $this->body = array_splice($this->body, 0, $cut_line);
          return;
        }

        list ($unwanted_text, $cut_line) = self::getLinesFromEnd($this->body, 2, true);
        $unwanted_text = trim(implode(null, $unwanted_text));
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
     * Convert HTML to plain text (email style)
     *
     * @param string $html
     * @param boolean $clean
     * @return string
     */
    static function toPlainText($html, $clean = false) {
      $plain = (string) $html;

      // strip slashes
      $plain = (string) trim(stripslashes($plain));

      // strip unnecessary characters
      $plain = (string) preg_replace(array(
      "/\r/", // strip carriage returns
      "/<script[^>]*>.*?<\/script>/si", // strip immediately, because we don't need any data from it
      "/<style[^>]*>.*?<\/style>/is", // strip immediately, because we don't need any data from it
      "/style=\".*?\"/"   //was: '/style=\"[^\"]*/'
      ), "", $plain);

      // entities to convert (this is not a definite list)
      $entities = array(
        ' '     => array('&nbsp;', '&#160;'),
        '"'     => array('&quot;', '&rdquo;', '&ldquo;', '&#8220;', '&#8221;', '&#147;', '&#148;'),
        '\''    => array('&apos;', '&rsquo;', '&lsquo;', '&#8216;', '&#8217;'),
        '>'     => array('&gt;'),
        '<'     => array('&lt;'),
        '&'     => array('&amp;', '&#38;'),
        '(c)'   => array('&copy;', '&#169;'),
        '(R)'   => array('&reg;', '&#174;'),
        '(tm)'  => array('&trade;', '&#8482;', '&#153;'),
        '--'    => array('&mdash;', '&#151;', '&#8212;'),
        '-'     => array('&ndash;', '&minus;', '&#8211;', '&#8722;'),
        '*'     => array('&bull;', '&#149;', '&#8226;'),
        '�'     => array('&pound;', '&#163;'),
        'EUR'   => array('&euro;', '&#8364;')
      );

      // convert specified entities
      foreach ($entities as $character => $entity) {
        $plain = (string) str_replace($entity, $character, $plain);
      }

      // strip other not previously converted entities
      $plain = (string) preg_replace(array(
      '/&[^&;]+;/si',
      ), "", $plain);

      // <p> converts to 2 newlines
      $plain = (string) preg_replace('/<p[^>]*>/i', "\n\n", $plain); // <p>

      // uppercase html elements
      $plain = (string) preg_replace_callback('/<h[123456][^>]*>(.*?)<\/h[123456]>/i', function($matches) {
        return "\n\n" . mb_strtoupper($matches[1]) . "\n\n";
      }, $plain); // <h1-h6>

      $plain = (string) preg_replace_callback(array('/<b[^>]*>(.*?)<\/b>/i', '/<strong[^>]*>(.*?)<\/strong>/i'), function($matches) {
        return mb_strtoupper($matches[1]);
      }, $plain); // <b> <strong>

      // deal with italic elements
      $plain = (string) preg_replace(array('/<i[^>]*>(.*?)<\/i>/i', '/<em[^>]*>(.*?)<\/em>/i'), '_\\1_', $plain); // <i> <em>

      // elements that convert to 2 newlines
      $plain = (string) preg_replace(array('/(<ul[^>]*>|<\/ul>)/i', '/(<ol[^>]*>|<\/ol>)/i', '/(<table[^>]*>|<\/table>)/i'), "\n\n", $plain); // <ul> <ol> <table>

      // elements that convert to single newline
      $plain = (string) preg_replace(array('/<br[^>]*>/i', '/(<tr[^>]*>|<\/tr>)/i'), "\n", $plain); // <br> <tr>

      // <hr> converts to -----------------------
      $plain = (string) preg_replace('/<hr[^>]*>/i', "\n-------------------------\n", $plain); // <hr>

      // other table tags
      $plain = (string) preg_replace('/<td[^>]*>(.*?)<\/td>/i', "\t\\1\n", $plain); // <td>
      $plain = (string) preg_replace_callback('/<th[^>]*>(.*?)<\/th>/i', function($matches) {
        return "\t\t" . mb_strtoupper($matches) . "\n";
      }, $plain); // <th>

      // list elements
      $plain = (string) preg_replace('/<li[^>]*>(.*?)<\/li>/i', "* \\1\n", $plain); // <li>with content</li>
      $plain = (string) preg_replace('/<li[^>]*>/i', "\n* ", $plain); // <li />

      // handle anchors
      $plain = (string) preg_replace_callback('/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i', function($matches) {
        $url = $matches[1];
        $text = $matches[2];

        if (EmailReplyExtractor::str_starts_with($url, 'http://') || EmailReplyExtractor::str_starts_with($url, 'https://')) {
          return "$text [$url]";
        } else if (EmailReplyExtractor::str_starts_with($url, 'mailto:')) {
          return $text . ' [' . substr($url, 7) . ']';
        } else {
          return $text;
        }
      }, $plain); // <li />

      // handle blockquotes
      $plain = (string) preg_replace_callback('/<blockquote[^>]*>(.*?)<\/blockquote>/is', function ($blockquote_content) {
        $blockquote_content = isset($blockquote_content[1]) ? $blockquote_content[1] : '';

        $lines = (array) explode("\n", $blockquote_content);
        $return = array();
        if (!empty($lines)) {
          foreach ($lines as $line) {
            $return[] = '> ' . $line;
          } // if
        } // if
        return "\n\n" . implode("\n", $return) . "\n\n";
      }, $plain);

      // strip other tags
      $plain = (string) strip_tags($plain);

      // clean up unneccessary newlines
      $plain = (string) preg_replace("/\n\s+\n/", "\n\n", $plain);
      $plain = (string) preg_replace("/[\n]{3,}/", "\n\n", $plain);

      return trim($plain);
    }
  }