<?php

use Azt3k\SS\Social\Extensions\SocialMediaPageExtension;
use Azt3k\SS\Social\Objects\SocialGlobalConf;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;

// Register shortcode and editor plugin for social embeds
if (!SocialGlobalConf::config()->get('disable_wysiwyg_embed')) {
    ShortcodeParser::get('default')->register(
        'social_embed',
        function (array $arguments, ?string $content = null, $parser = null, ?string $tagName = null): ?string {
            return SocialMediaPageExtension::SocialEmbedParser($arguments, $content, $parser, $tagName);
        }
    );
    HTMLEditorConfig::get('cms')->enablePlugins([
        'social_embed' => '/vendor/azt3k/abc-silverstripe-social/js/editor-plugin.js',
    ]);
    HTMLEditorConfig::get('cms')->addButtonsToLine(2, 'social_embed');
}
