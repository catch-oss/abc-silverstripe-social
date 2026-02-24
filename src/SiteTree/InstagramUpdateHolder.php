<?php

namespace Azt3k\SS\Social\SiteTree;

use Page;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;

/**
 * @author AzT3k
 */
class InstagramUpdateHolder extends Page
{

    private static $table_name = 'InstagramHolder';
    private static $can_be_root = true;
    private static $allowed_children = [
        InstagramUpdate::class
    ];
}
