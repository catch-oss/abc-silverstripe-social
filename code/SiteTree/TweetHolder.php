<?php

namespace Azt3k\SS\Social\SiteTree;
use Page;
use SilverStripe\CMS\Model\SiteTree;
use Azt3k\SS\Social\SiteTree\Tweet;
use SilverStripe\Control\Controller;

/**
 * Description of Tweet
 *
 * @author AzT3k
 */
class TweetHolder extends Page {

    private static $table_name = 'TweetHolder';
	private static $can_be_root = true;
    private static $allowed_children = array(
        Tweet::class
    );

}

class TweetHolder_Controller extends Controller {

}
