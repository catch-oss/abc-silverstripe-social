<?php

namespace Azt3k\SS\Social\SiteTree;

use Page;
use GuzzleHttp\Client as GuzzleClient;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Assets\Image;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\LiteralField;

/**
 * @author AzT3k
 */
class FBUpdate extends Page {

    private static $table_name = 'FBUpdate';

    private static $db = array(
        'UpdateID'          => 'Varchar(255)',
        'OriginalCreated'   => 'Datetime',
        'OriginalUpdate'    => 'Text'
    );

    private static $owns = [
        'PrimaryImage'
    ];

    private static $has_one = array(
        'PrimaryImage'      => Image::class,
    );

    private static $defaults = array(
        'holder_class'      => 'FBUpdateHolder',
    );

    /**
     * Facebook placeholder image URLs to exclude when downloading post images.
     * @config
     */
    private static $placeholder_images = [
        'https://fbstatic-a.akamaihd.net/rsrc.php/v2/yA/r/gPCjrIGykBe.gif',
        'https://fbstatic-a.akamaihd.net/rsrc.php/v2/y6/r/_xS7LcbxKS4.gif',
    ];

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

    public function __construct($record = null, $isSingleton = false) {
        parent::__construct($record, $isSingleton);
        $this->configure();
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

    public function resolveUrl(string $url): string
    {

        try {
            $client = new GuzzleClient(['allow_redirects' => ['track_redirects' => true]]);
            $response = $client->head($url);
            $redirects = $response->getHeader('X-Guzzle-Redirect-History');
            return !empty($redirects) ? end($redirects) : $url;
        } catch (\Exception $e) {
            return $url;
        }
    }

    public function updateFromUpdate(\stdClass $update, bool $save = true): mixed
    {

        // print_r($update);
        $pageid = SiteConfig::current_site_config()->FacebookPageId;
        $postid = str_replace($pageid . '_', '', $update->id);

        try {
            $picUrl = $this->resolveUrl('https://graph.facebook.com/' . $postid . '/picture');
        } catch (Exception $e) {
            $picUrl = '';
        }

        $placeholders = Config::inst()->get(static::class, 'placeholder_images') ?: [];
        if ($picUrl && !in_array($picUrl, $placeholders)) {

            // get url
            $img = $picUrl;

            // sanity check
            if (!is_dir(ASSETS_PATH . '/social-updates/')) mkdir(ASSETS_PATH . '/social-updates/');

            // prep img data - sanitize basename to prevent path traversal
            $noq = explode('?', $img);
            $basename = basename(pathinfo($noq[0], PATHINFO_BASENAME));
            $absPath = ASSETS_PATH . '/social-updates/' . $basename;
            $relPath = ASSETS_DIR . '/social-updates/' . $basename;

            // pull down image
            if (!file_exists($absPath)) {
                $imgData = file_get_contents($img);
                if ($imgData !== false) {
                    file_put_contents($absPath, $imgData);
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
        }

        // extract content
        $content = $update->message;
        $content = $content ?: $update->description;
        $content = $content ?: $update->story;

        if (!$content) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                'FBUpdate: No content found for update ' . ($update->id ?? 'unknown')
            );
            return false;
        }
        else {

            $this->Title                = 'Facebook Update - ' . $update->id;
            $this->URLSegment           = 'FBUpdate-' . $update->id;
            $this->UpdateID             = $update->id;
            $this->OriginalCreated      = date('Y-m-d H:i:s',strtotime($update->created_time));
            $this->Content              = $content;
            $this->OriginalUpdate       = json_encode($update);

            $this->findParent();

            return $save ? $this->write() : true ;
        }

    }

    public function getCMSFields(): FieldList
    {

        $fields = parent::getCMSFields();

        $lastEditedDateField = new DatetimeField('OriginalCreated');
        // $lastEditedDateField->setConfig('showcalendar', true);
        $fields->addFieldToTab('Root.Main', $lastEditedDateField, 'Content');

        $fields->addFieldToTab('Root.Original', new LiteralField('OriginalUpdate', str_replace("\n", '<br>', print_r($this->OriginalUpdate,1))));

        return $fields;

    }

    public function OriginalLink(): string
    {
        $id = SiteConfig::current_site_config()->FacebookPageId;
        return 'https://www.facebook.com/' .
            $id .
            '/posts/' .
            str_replace($id . '_', '', $this->UpdateID);
    }

    /**
     * Adds all the tweet fields on to this object rather than just the ones we have seperated out
     *
     * @return \FBUpdate
     */
    public function expandUpdateData(?\stdClass $update = null): static
    {

        $data = $update ? json_decode(json_encode($update),true) : json_decode($this->OriginalUpdate,true) ;

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
