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
        '/On(.*?)wrote\:(.*?)/is'
      ]);
    }
  }