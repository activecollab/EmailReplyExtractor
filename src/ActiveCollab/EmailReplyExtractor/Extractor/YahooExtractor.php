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
    protected function getOriginalMessageSplitters()
    {
      return array_merge(parent::getOriginalMessageSplitters(), [
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
      $html = str_replace(['span', 'div'], 'p', $html);
      $html = preg_replace('/<div class="signature".+<\/div>/','', $html);
      return parent::toPlainText($html);
    }

  }