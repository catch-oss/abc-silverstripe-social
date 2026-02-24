<?php

namespace Azt3k\SS\Social\DataObjects;

use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\SiteTree;

/**
 * @author AzT3k
 */
class PublicationTweet extends DataObject {

    private static $table_name = 'PublicationTweet';

    private static $db = array(
        'TweetID' => 'Varchar(255)'
    );

    private static $has_one = array(
        'Page' => SiteTree::class
    );

}
