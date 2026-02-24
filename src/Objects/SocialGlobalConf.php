<?php

namespace Azt3k\SS\Social\Objects;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
/**
 * @author AzT3k
 */
class SocialGlobalConf {

    // use SilverStripe\Core\Extensible;
    // use SilverStripe\Core\Injector\Injectable;
    // use SilverStripe\Core\Config\Configurable;
    use Extensible, Injectable, Configurable;

    /**
     * Disable the [social_embed] shortcode handler.
     * @config
     */
    private static $disable_shortcode_embed;

    /**
     * Legacy config key — use disable_shortcode_embed instead.
     * @config
     * @deprecated Use disable_shortcode_embed
     */
    private static $disable_wysiwyg_embed;
}
