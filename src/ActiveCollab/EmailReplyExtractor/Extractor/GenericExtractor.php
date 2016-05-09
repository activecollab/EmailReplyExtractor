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
    protected function getAllMessageSplitters()
    {
      return array_merge(parent::getAllMessageSplitters(), [
        '/\-------------------------/is',
      ]);
    }
  }