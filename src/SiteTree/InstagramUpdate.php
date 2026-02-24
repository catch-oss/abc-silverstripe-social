<?php

namespace Azt3k\SS\Social\SiteTree;

use Page;
use Azt3k\SS\Social\SiteTree\InstagramUpdateHolder;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Assets\Image;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Assets\Filesystem;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\LiteralField;
use Azt3k\SS\Social\Objects\SocialHelper;

/**
 * @author AzT3k
 */
class InstagramUpdate extends Page
{

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
    public static function set_conf(array|object $conf): void
    {
        $conf = (array) $conf;
        static::$conf = array_merge(static::$conf, $conf);
    }

    /**
     *  @return stdClass
     */
    public static function get_conf(): object
    {
        return (object) array_merge(static::$defaults, static::$conf);
    }

    /**
     * @return void
     */
    protected static function set_conf_from_yaml(): void
    {
        $conf = (array) Config::inst()->get(__CLASS__, 'conf');
        if (!empty($conf))
            static::$conf = array_merge(static::$conf, $conf);
    }

    /**
     *  @return void
     */
    protected function configure(): void
    {
        static::set_conf_from_yaml();
    }

    public function __construct($record = null, $isSingleton = false)
    {
        parent::__construct($record, $isSingleton);
        $this->configure();
    }

    public function updateFromUpdate(\stdClass $update, bool $save = true): mixed
    {

        if (is_array($update)) {
            $update = json_decode(json_encode($update));
        }

        $content = $update->caption ?? '';
        $img = $update->media_url ?? '';
        if (!empty($update->media_type) && $update->media_type === 'VIDEO' && !empty($update->thumbnail_url)) {
            $img = $update->thumbnail_url;
        }

        if (!$content && !$img) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                'InstagramUpdate: No content or image found for update ' . ($update->id ?? 'unknown')
            );
            return false;
        } else {

            // ensure directory exists
            $dir = ASSETS_PATH . '/social-updates/';
            Filesystem::makeFolder($dir);

            // prep img data - sanitize basename to prevent path traversal
            $basename = basename(pathinfo($img, PATHINFO_BASENAME));
            $absPath = $dir . $basename;
            $relPath = ASSETS_DIR . '/social-updates/' . $basename;

            // pull down image with size limit and content validation
            if (!file_exists($absPath)) {
                $imgData = SocialHelper::downloadImage($img);
                if ($imgData !== false) {
                    file_put_contents($absPath, $imgData);
                } else {
                    Injector::inst()->get(LoggerInterface::class)->warning(
                        'InstagramUpdate: Skipped image download — failed, invalid or oversized: ' . $img
                    );
                }
            }

            // does the file exist
            if (file_exists($absPath)) {

                // try to find the existing image
                if (!$image = Image::get()->filter('Filename', $relPath)->first()) {

                    // create image record
                    $image = new Image;
                    $image->setFilename($relPath);
                    $image->write();
                    $image->publishRecursive();
                }

                // associate
                if ($image->ID) $this->PrimaryImageID = $image->ID;
            }

            // update
            $this->Title            = 'Instagram Update - ' . $update->id;
            $this->URLSegment       = 'InstagramUpdate-' . $update->id;
            $this->UpdateID         = $update->id;
            $timestamp = $update->timestamp ?? null;
            $this->OriginalCreated  = $timestamp ? date('Y-m-d H:i:s', strtotime($timestamp)) : null;
            $this->Content          = $content;
            $this->OriginalUpdate   = json_encode($update);
            $this->findParent();

            return $save ? $this->write() : true;
        }
    }

    public function getCMSFields(): FieldList
    {

        $fields = parent::getCMSFields();

        $lastEditedDateField = new DatetimeField('OriginalCreated');
        // $lastEditedDateField->setConfig('showcalendar', true);
        $fields->addFieldToTab('Root.Main', $lastEditedDateField, 'Content');

        $fields->addFieldToTab('Root.Original', new LiteralField('OriginalUpdate', str_replace("\n", '<br>', print_r($this->OriginalUpdate, 1))));

        return $fields;
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        $this->findParent();
    }

    public function findParent(): void
    {
        if (!$this->ParentID) {
            $conf = static::get_conf();
            if (!$parent = DataObject::get_one($conf->holder_class)) {
                $parent = new $conf->holder_class;
                $parent->write();
                $parent->publishRecursive();
            }
            $this->ParentID = $parent->ID;
        }
    }

    public function OriginalLink(): ?string
    {
        if (!$this->OriginalUpdate) return null;

        $data = json_decode($this->OriginalUpdate);

        if (!$data) return null;

        return $data->permalink ?? $data->link ?? null;
    }

    /**
     * Adds all the tweet fields on to this object rather than just the ones we have seperated out
     *
     * @return \InstagramUpdate
     */
    public function expandUpdateData(?\stdClass $update = null): static
    {
        $data = $update ? json_decode(json_encode($update), true) : json_decode($this->OriginalUpdate, true);
        $this->customise($data);
        return $this;
    }

    /**
     * Override canPublish check to allow publish from CLI
     * @param type $member
     * @return boolean
     */
    public function canPublish($member = null): bool
    {
        if (Director::is_cli()) return true;
        else return parent::canPublish($member);
    }
}
