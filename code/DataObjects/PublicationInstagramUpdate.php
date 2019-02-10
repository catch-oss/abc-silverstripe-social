<?php

namespace Azt3k\SS\Social\DataObjects;

use SilverStripe\ORM\DataObject;

/**
 * @author AzT3k
 */
class PublicationInstagramUpdate extends DataObject {

    private static $table_name = 'PublicationInstagramUpdate';

    private static $db = array(
        'InstagramUpdateID' => 'Varchar(255)'
    );

    private static $has_one = array(
        'Page' => 'Page'
    );
}
