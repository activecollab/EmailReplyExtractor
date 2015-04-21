<?php
  namespace ActiveCollab\EmailReplyExtractor\Test;

  use ActiveCollab\EmailReplyExtractor;
  use DirectoryIterator;

  /**
   * @package ActiveCollab\EmailReplyExtractor\Test
   */
  class ExampleMessagesTest extends TestCase
  {
    /**
     * Test example messages
     */
    public function testExampleMessages()
    {
      foreach (new DirectoryIterator(dirname(dirname(dirname(__DIR__))) . '/example_messages') as $file) {
        if ($file->isDot() || $file->isDir()) {
          continue;
        }

        if ($file->isFile() && $file->getExtension() == 'eml') {
          EmailReplyExtractor::extract($file->getPathname());
        }
      }
    }
  }