<?php

namespace Azt3k\SS\Social\DataObjects;

use SilverStripe\ORM\DataObject;

/**
 * @author AzT3k
 */
class PublicationFBUpdate extends DataObject {

    private static $table_name = 'PublicationFBUpdate';

    private static $db = array(
        'FBUpdateID' => 'Varchar(255)'
    );

    private static $has_one = array(
        'Page' => 'Page'
    );
}
