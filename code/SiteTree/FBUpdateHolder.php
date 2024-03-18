<?php

namespace Azt3k\SS\Social\SiteTree;

use Page;
use SilverStripe\CMS\Model\SiteTree;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use SilverStripe\Control\Controller;

/**
 * @author AzT3k
 */
class FBUpdateHolder extends Page
{

    private static $can_be_root = true;
    private static $allowed_children = array(
        FBUpdate::class
    );
}
