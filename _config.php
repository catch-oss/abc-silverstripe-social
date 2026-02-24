<?php

use Azt3k\SS\Social\Extensions\SocialMediaPageExtension;
use Azt3k\SS\Social\Objects\SocialGlobalConf;
use SilverStripe\View\Parsers\ShortcodeParser;

// Register shortcode for social embeds
// Note: TinyMCE editor plugin removed in SS6 (TinyMCE no longer bundled)
if (!SocialGlobalConf::config()->get('disable_wysiwyg_embed')) {
    ShortcodeParser::get('default')->register(
        'social_embed',
        function (array $arguments, ?string $content = null, $parser = null, ?string $tagName = null): ?string {
            return SocialMediaPageExtension::SocialEmbedParser($arguments, $content, $parser, $tagName);
        }
    );
}
