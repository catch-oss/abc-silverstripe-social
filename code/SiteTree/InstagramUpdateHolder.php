<?php

namespace Azt3k\SS\Social\SiteTree;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;

/**
 * @author AzT3k
 */
class InstagramHolder extends SiteTree {

    private static $table_name = 'InstagramHolder';
	private static $can_be_root = true;
    private static $allowed_children = array(
        'Instagram'
    );

}

class InstagramHolder_Controller extends Controller {

}
