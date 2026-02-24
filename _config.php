<?php

use Azt3k\SS\Social\Extensions\SocialMediaPageExtension;
use Azt3k\SS\Social\Objects\SocialGlobalConf;
use SilverStripe\View\Parsers\ShortcodeParser;

// Register [social_embed] shortcode handler for embedding social media posts in content.
// Disable via YAML: Azt3k\SS\Social\Objects\SocialGlobalConf.disable_shortcode_embed: true
if (
    !SocialGlobalConf::config()->get('disable_shortcode_embed')
    && !SocialGlobalConf::config()->get('disable_wysiwyg_embed') // legacy config key
) {
    ShortcodeParser::get('default')->register(
        'social_embed',
        function (array $arguments, ?string $content = null, $parser = null, ?string $tagName = null): ?string {
            return SocialMediaPageExtension::SocialEmbedParser($arguments, $content, $parser, $tagName);
        }
    );
}
