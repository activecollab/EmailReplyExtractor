<?php

namespace ActiveCollab\EmailReplyExtractor\Extractor;

/**
 */
final class OutlookExtractor extends Extractor
{
    /**
     * Overrides Extractor::stripSignature().
     */
    public function stripSignature()
    {
        for ($x = 0, $lines_count = count($this->body); $x < $lines_count; ++$x) {
            $line = trim($this->body[(($lines_count - $x) - 1)]);

            if ($line && trim($line)) {
                if ($line == '-- ' || $line == '--' || substr($line, 0, strlen('-- ')) == '-- ') {
                    $this->body = array_splice($this->body, 0, (($lines_count - $x) - 1));

                    return;
                }
            }
        }
    }

    /**
     * Return original message splitters.
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
     * Process body text
     */
    protected function processLines()
    {
        parent::processLines();
        $this->stripSignature();
    }


    /**
     * Strip default Outlook for Mac signature.
     *
     * @param string $html
     *
     * @return string
     */
    public static function toPlainText($html)
    {
        $html = preg_replace('/<div id="MAC_OUTLOOK_SIGNATURE".+<\/div>/', '', $html);
        $html = preg_replace('/<link[^>]*>/i', '', $html);

        return parent::toPlainText($html);
    }
}
