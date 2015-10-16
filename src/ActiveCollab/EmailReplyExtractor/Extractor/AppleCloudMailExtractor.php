<?php


namespace ActiveCollab\EmailReplyExtractor\Extractor;


class AppleCloudMailExtractor extends Extractor
{

  /**
   * Find reply separator and remove lines that are after it
   *
   * @param  array   $splitters
   * @param  integer $trim_previous_lines
   */
  protected function stripOriginalMessage(array &$splitters, $trim_previous_lines = 0)
  {
    $stripped = [];

    foreach ($this->body as $line) {
        if (strpos($line, '--Boundary_') !== false) {
          if ($trim_previous_lines == 0) {
            $this->body = $stripped;
          } else {
            $this->body = array_slice($stripped, 0, count($stripped) - $trim_previous_lines);
          }

          $this->stripEmptyLinesFromTheEnd();
          return;
        }
      $stripped[] = $line;
    }
  }

  /**
   * Extract Reply from Apple MAil mail
   */
  protected function processLines()
  {
    $splitters = $this->getOriginalMessageSplitters();

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