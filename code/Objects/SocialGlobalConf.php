<?php

namespace Azt3k\SS\Social\Objects;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;
/**
 * @author AzT3k
 */
class SocialGlobalConf {

    use SilverStripe\Core\Extensible;
    use SilverStripe\Core\Injector\Injectable;
    use SilverStripe\Core\Config\Configurable;

    /**
     * @config
     */
    private static $disable_wysiwyg_embed;
}
