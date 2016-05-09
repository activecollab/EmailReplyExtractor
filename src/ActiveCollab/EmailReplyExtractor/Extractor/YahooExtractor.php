<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class YahooExtractor extends Extractor
  {
    /**
     * Return splitters
     *
     * @return array
     */
    protected function getAllMessageSplitters()
    {
      return array_merge(parent::getAllMessageSplitters(), [
        '/\-------------------------/is',
      ]);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    static function toPlainText($html)
    {
      $html = str_replace('span', 'p', $html);
      $html = preg_replace('/<div class="signature".+<\/div>/','', $html);
      return parent::toPlainText($html);
    }

  }