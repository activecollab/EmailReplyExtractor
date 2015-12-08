<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class OutlookExtractor extends Extractor
  {
    /**
     * Overrides Extractor::toPlaineText()
     *
     * @param string $html
     *
     * @return string
     */
    static function toPlainText($html)
    {
      $html = str_replace('div', 'p', $html);

      return parent::toPlainText($html);
    }


    public function processLines()
    {
      parent::processLines();
      self::stripSignature();
    }

    /**
     * Overrides Extractor::stripSignature()
     */
    public function stripSignature()
    {
      for ($x = 0, $lines_count = count($this->body); $x < $lines_count; $x++) {
        $line = trim($this->body[(($lines_count - $x) - 1)]);

        if ($line && trim($line)) {
          if ($line == "-- " || $line == "--" || substr($line, 0, strlen('-- ')) == '-- ') {
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
     * Return original message splitters
     *
     * @todo
     * @return array
     */
    protected function getOriginalMessageSplitters()
    {
      return array_merge(parent::getOriginalMessageSplitters(), [
        '/\-------------------------/is',
      ]);
    }

  }