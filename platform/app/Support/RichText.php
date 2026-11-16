<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitises rich text from the admin editor before it is rendered on the
 * public proposal page or PDF. Strips scripts, event handlers, iframes, etc.
 */
class RichText
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->allowRelativeLinks(false)
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->forceAttribute('a', 'target', '_blank')
                ->dropElement('img') // images belong in the screenshot uploader
        );

        return self::$sanitizer->sanitize($html);
    }

    public static function isEmpty(?string $html): bool
    {
        return trim(strip_tags((string) $html)) === '';
    }
}
