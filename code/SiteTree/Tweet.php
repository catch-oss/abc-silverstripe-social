<?php

namespace Azt3k\SS\Social\SiteTree;

use Page;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Assets\Image;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use Azt3k\SS\Social\SiteTree\TweetHolder;
use SilverStripe\Forms\LiteralField;

/**
 * Description of Tweet
 *
 * @author AzT3k
 */
class Tweet extends Page {

    private static $table_name = 'Tweet';

    private static $db = array(
        'TweetID'           => 'Varchar(255)',
        'OriginalCreated'   => 'Datetime',
        'OriginalTweet'     => 'Text'
    );

    private static $owns = [
        'PrimaryImage'
    ];

    private static $has_one = array(
        'PrimaryImage'      => Image::class
    );

    private static $defaults = array(
        'holder_class'      => TweetHolder::class
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

    public function updateFromTweet(\stdClass $tweet, $save = true) {

        // echo json_encode($tweet, JSON_PRETTY_PRINT);

        if (!empty($tweet->entities->media[0])) {

            // extract media
            $media = $tweet->entities->media[0];

            // only process photos
            if ($media->type == 'photo') {

                // get url
                $img = $media->media_url;

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

                // echo $img;
                // echo $absPath;
                // echo $relPath;

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

            }
        }

        $this->Title            = 'Tweet - '.$tweet->id_str;
        $this->URLSegment       = 'Tweet-'.$tweet->id_str;
        $this->TweetID          = $tweet->id_str;
        $this->OriginalCreated  = date('Y-m-d H:i:s',strtotime($tweet->created_at));
        $this->Content          = $tweet->text;
        $this->OriginalTweet    = json_encode($tweet);
		$this->findParent();

        return $save ? $this->write() : true ;

    }

    public function getCMSFields() {

        $fields = parent::getCMSFields();

        $lastEditedDateField = new DatetimeField('OriginalCreated');
        // $lastEditedDateField->setConfig('showcalendar', true);
        $fields->addFieldToTab('Root.Main', $lastEditedDateField, 'Content');

        $fields->addFieldToTab('Root.Original', new LiteralField('OriginalTweet', str_replace("\n", '<br>', print_r($this->OriginalTweet,1))));

        return $fields;

    }

    public function OriginalLink() {
        return 'https://twitter.com/' .
            SiteConfig::current_site_config()->TwitterUsername .
            '/status/' .
            $this->TweetID;
    }

    /**
     * Adds all the tweet fields on to this object rather than just the ones we have seperated out
     *
     * @return \Tweet
     */
    public function expandTweetData(\stdClass $tweet = null){
        $data = $tweet ? json_decode(json_encode($tweet),true) : json_decode($this->OriginalTweet,true);
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

    /**
     * Parses the tokens out of the html
     */
    public function Content() {

        // links
        $text = preg_replace(
            '@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@',
             '<a href="$1">$1</a>',
            $this->Content
        );

        // users
        $text = preg_replace(
            '/@(\w+)/',
            '<a href="http://twitter.com/$1">@$1</a>',
            $text
        );

        // hashtags
        $text = preg_replace(
            '/\s+#(\w+)/',
            ' <a href="http://search.twitter.com/search?q=%23$1">#$1</a>',
            $text
        );

        return DBField::create_field(
            'HTMLText',
            $text
        );
    }

}

class Tweet_Controller extends Controller {

}
