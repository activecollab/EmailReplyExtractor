<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class GoogleMailExtractor extends Extractor
  {
    /**
     * Extract reply from andorid mail client
     */
    function processLines() {
      parent::processLines();

      list ($unwanted_text, $cut_line) = self::getLinesFromEnd(1);
      $unwanted_text = implode(null, $unwanted_text);

      // strip 'first name last name wrote:'
      if (preg_match('/(.*?)wrote:/is', $unwanted_text)) {
        $this->body = array_splice($this->body, 0, $cut_line);
      }

      // default signature
      $match_string = '^sent from(.*?)';
      // strip default signature
      if ($match_string) {
        list ($default_signature, $cut_line) = self::getLinesFromEnd(1);
        $default_signature = implode(null, $default_signature);
        if (preg_match('/' . $match_string . '/is', $default_signature)) {
          $this->body= array_splice($this->body, 0, $cut_line);
        }
      }

      $this->stripSignature();
    }
  }