<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class iOSExtractor extends Extractor
  {
    protected function processLines()
    {
      parent::processLines();

      // default signature
      $match_string = '^sent from(.*?)';
      // strip default signature
      if ($match_string) {
        list ($default_signature, $cut_line) = self::getLinesFromEnd(1);
        $default_signature = implode('', $default_signature);
        if (preg_match('/' . $match_string . '/is', $default_signature)) {
          $this->body= array_splice($this->body, 0, $cut_line);
        }
      }

      $this->stripSignature();
    }
  }
