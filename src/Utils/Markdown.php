<?php



namespace App\Utils;

use HtmlSanitizer\SanitizerInterface;

/**
 */
class Markdown
{
    private $parser;
    private $sanitizer;

    public function __construct(SanitizerInterface $sanitizer)
    {
        $this->parser = new \Parsedown();
        $this->sanitizer = $sanitizer;
    }

    public function toHtml(string $text): string
    {
        $html = $this->parser->text($text);
        $safeHtml = $this->sanitizer->sanitize($html);

        return $safeHtml;
    }
}
