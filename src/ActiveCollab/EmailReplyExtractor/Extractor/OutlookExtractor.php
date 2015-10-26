<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class OutlookExtractor extends Extractor
  {
    /**
     * @param string $html
     *
     * @return string
     */
    static function toPlainText($html)
    {
      $html = str_replace('div', 'p', $html);

      return parent::toPlainText($html);
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