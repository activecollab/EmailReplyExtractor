<?php
  namespace ActiveCollab\EmailReplyExtractor\Extractor;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Extractor
   */
  final class GenericExtractor extends Extractor
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
  }