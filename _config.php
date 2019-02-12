<?php

use Azt3k\SS\Social\SiteTree\Tweet;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\Extensions\SocialMediaConfig;
use Azt3k\SS\Social\Extensions\SocialUpdatePageExtension;

use SilverStripe\Core\Config\Config;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;

// Define path constant
$path = str_replace('\\', '/', __DIR__);
$path_fragments = explode('/', $path);
$dir_name = $path_fragments[count($path_fragments) - 1];
define('ABC_SOCIAL_DIR', $dir_name);

// attach the social extensions to the config and page classes
SiteConfig::add_extension(SocialMediaConfig::class);
Page::add_extension(SocialUpdatePageExtension::class);

// attach common behaviours to the social updates
FBUpdate::add_extension(SocialUpdatePageExtension::class);
Tweet::add_extension(SocialUpdatePageExtension::class);
InstagramUpdate::add_extension(SocialUpdatePageExtension::class);

// add the embed functionality
if (!Config::inst()->get('SocialGlobalConf', 'disable_wysiwyg_embed')) {
    //$this->shortcodes[$tag], $attributes, $content, $this, $tag, $extra);

    ShortcodeParser::get('default')->register('social_embed', function($arguments, $content = null, $parser = null, $tagName){
        return SocialUpdatePageExtension::SocialEmbedParser($arguments, $content, $parser, $tagName);
    });
    HtmlEditorConfig::get('cms')->enablePlugins(array(
        'social_embed' => '../../../' . ABC_SOCIAL_DIR . '/js/editor-plugin.js'
    ));
    HtmlEditorConfig::get('cms')->addButtonsToLine(2, 'social_embed');
}

// allow script tags
// maybe we could try using requirements and stripping the script tags
// HtmlEditorConfig::get('cms')
//     ->setOption(
//         'extended_valid_elements',
//         'img[class|src|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|usemap|data*],' .
//         'iframe[src|name|width|height|align|frameborder|marginwidth|marginheight|scrolling],' .
//         'object[width|height|data|type],' .
//         'param[name|value],' .
//         'map[class|name|id],' .
//         'area[shape|coords|href|target|alt],ol[class|start],' .
//         'script[type|src|lang|async|charset]'
//     );
