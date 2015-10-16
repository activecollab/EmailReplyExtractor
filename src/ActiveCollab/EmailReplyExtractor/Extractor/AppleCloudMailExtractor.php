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
  protected function stripOriginalMessage(array &$splitters, $trim_previous_lines = 0) {
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
}