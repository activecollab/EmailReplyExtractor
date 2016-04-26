<?php
namespace ActiveCollab\EmailReplyExtractor\Extractor;


final class ThunderBirdMailExtractor extends Extractor
{
    /**
     * Extract Reply from ThunderBird mail
     */
    protected function processLines()
    {
        $splitters = $this->getOriginalMessageSplitters();

        if (!empty($splitters)) {
            $this->stripOriginalMessage($splitters);
        }

        $this->body = implode("\n", $this->body);
        if (preg_match('/(.*)(On)(.*) wrote\:(.*)/mis', $this->body, $matches, PREG_OFFSET_CAPTURE)) {
            $match_index = $matches[1][0];
            $this->body = trim($match_index);
        }
        $this->body = explode("\n", $this->body);

        $unwanted_text_patterns = $this->getUnwantedTextPatterns();

        if (!empty($unwanted_text_patterns)) {
            $this->stripUnwantedText($unwanted_text_patterns);
        }

        $this->stripSignature();
        $this->convertPlainTextQuotesToBlockquotes();
    }

    /**
     * Return original message splitters
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