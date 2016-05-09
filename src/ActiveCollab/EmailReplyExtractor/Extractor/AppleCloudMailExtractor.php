<?php


namespace ActiveCollab\EmailReplyExtractor\Extractor;


class AppleCloudMailExtractor extends Extractor
{
  /**
   * Extract Reply from Apple MAil mail
   */
  protected function processLines()
  {
    $splitters = $this->getAllMessageSplitters();

    if (!empty($splitters)) {
      $this->stripOriginalMessage($splitters);
    }

    $this->body = implode("\n", $this->body);
    if (preg_match('/(.*)(On)(.*) at (.*) wrote\:(.*)/mis', $this->body, $matches, PREG_OFFSET_CAPTURE)) {
      $match_index = $matches[2][1];
      $this->body = trim(mb_substr($this->body, 0, $match_index));
    }
    $this->body = explode("\n", $this->body);

    $unwanted_text_patterns = $this->getUnwantedTextPatterns();

    if (!empty($unwanted_text_patterns)) {
      $this->stripUnwantedText($unwanted_text_patterns);
    }

    $this->stripSignature();
    $this->convertPlainTextQuotesToBlockquotes();
  }
}