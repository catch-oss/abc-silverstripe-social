<?php

namespace Azt3k\SS\Social\SiteTree;

use Page;
use Azt3k\SS\Social\SiteTree\InstagramUpdateHolder;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use MetzWeb\Instagram\Instagram;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\LiteralField;

/**
 * @author AzT3k
 */
class InstagramUpdate extends Page {

    private static $table_name = 'InstagramUpdate';
    /**
     * @var array
     */
    private static $db = array(
        'UpdateID'          => 'Varchar(255)',
        'OriginalCreated'   => 'Datetime',
        'OriginalUpdate'    => 'Text'
    );

    private static $owns = [
        'PrimaryImage'
    ];

    /**
     * @var array
     */
    private static $has_one = array(
        'PrimaryImage' => Image::class,
    );

    /**
     * @var array
     */
    private static $defaults = array(
        'holder_class' => InstagramUpdateHolder::class
    );

    /**
     * @config
     */
    private static $conf = array();

    /**
     *  @param  array|object $conf An associative array containing the configuration - see static::$conf for an example
     *  @return void
     */
    public static function set_conf($conf) {
        $conf = (array) $conf;
        static::$conf = array_merge(static::$conf, $conf);
    }

    /**
     *  @return stdClass
     */
    public static function get_conf() {
        return (object) array_merge(static::$defaults, static::$conf);
    }

    /**
     * @return void
     */
    protected static function set_conf_from_yaml() {
        $conf = (array) Config::inst()->get(__CLASS__, 'conf');
        if (!empty($conf))
            static::$conf = array_merge(static::$conf, $conf);
    }

    /**
     *  @return void
     */
    protected function configure() {
        static::set_conf_from_yaml();
    }

    public function __construct($record = null, $isSingleton = false, $model = null) {
        parent::__construct($record, $isSingleton, $model);
        $this->configure();
    }

    public function updateFromUpdate(\stdClass $update, $save = true) {

        $content = empty($update->caption) ? '' : $update->caption->text;
        $img = empty($update->images) ? '' : $update->images->standard_resolution->url;

        if (!$content && !$img) {
            echo 'Encountered error with: ' . print_r($update,1);
            return false;
        }
        else {

            // sanity check
            if (!is_dir(ASSETS_PATH . '/social-updates/')) mkdir(ASSETS_PATH . '/social-updates/');

            // prep img data
            $pi = pathinfo($img);
            $absPath = ASSETS_PATH . '/social-updates/' . $pi['basename'];
            $relPath = ASSETS_DIR . '/social-updates/' . $pi['basename'];

            // pull down image
            if (!file_exists($absPath)) {
                $imgData = file_get_contents($img);
                file_put_contents($absPath, $imgData);
            }

            // does the file exist
            if (file_exists($absPath)) {

                // try to find the existing image
                if (!$image = DataObject::get_one(Image::class, "Filename='" . $relPath . "'")) {

                    // create image record
                    $image = new Image;
                    $image->setFilename($relPath);
                    $image->write();
                    $image->doPublish();
                }

                // associate
                if ($image->ID) $this->PrimaryImageID = $image->ID;
            }

            // update
            $this->Title            = 'Instagram Update - ' . $update->id;
            $this->URLSegment       = 'InstagramUpdate-' . $update->id;
            $this->UpdateID         = $update->id;
            $this->OriginalCreated  = date('Y-m-d H:i:s', $update->created_time);
            $this->Content          = $content;
            $this->OriginalUpdate   = json_encode($update);
            $this->findParent();

            return $save ? $this->write() : true;
        }
    }

    public function getCMSFields() {

        $fields = parent::getCMSFields();

        $lastEditedDateField = new DatetimeField('OriginalCreated');
        // $lastEditedDateField->setConfig('showcalendar', true);
        $fields->addFieldToTab('Root.Main', $lastEditedDateField, 'Content');

        $fields->addFieldToTab('Root.Original', new LiteralField('OriginalUpdate', str_replace("\n", '<br>', print_r($this->OriginalUpdate,1))));

        return $fields;

    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        $this->findParent();
    }

    public function findParent() {
        if (!$this->ParentID) {
            $conf = static::get_conf();
            if (!$parent = DataObject::get_one($conf->holder_class)) {
                $parent = new $conf->holder_class;
                $parent->write();
                $parent->doPublish();
            }
            $this->ParentID = $parent->ID;
        }
    }

    public function OriginalLink() {
        if(!$this->OriginalUpdate) return null;

        $data = json_decode($this->OriginalUpdate);

        if(!$data) return null;

        return $data->link;
    }

    /**
     * Adds all the tweet fields on to this object rather than just the ones we have seperated out
     *
     * @return \InstagramUpdate
     */
    public function expandUpdateData(\stdClass $update = null){
        $data = $update ? json_decode(json_encode($update),true) : json_decode($this->OriginalUpdate,true) ;
        $this->customise($data);
        return $this;
    }

    /**
     * Override canPublish check to allow publish from CLI
     * @param type $member
     * @return boolean
     */
    public function canPublish($member = null) {
        if (Director::is_cli()) return true;
        else return parent::canPublish($member);
    }

}

class InstagramUpdate_Controller extends Controller {

}
