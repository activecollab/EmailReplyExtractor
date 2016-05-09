<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class OutlookExpressExtractor extends Extractor
  {

    /**
     * Return original message splitters
     *
     * @return array
     */
    protected function getAllMessageSplitters()
    {
      return array_merge(parent::getAllMessageSplitters(), [
        '/\-------------------------/is',
      ]);
    }

  }