<?php

namespace Azt3k\SS\Social\Extensions;

use Azt3k\SS\Classes\AbcStr;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use Azt3k\SS\Social\SiteTree\Tweet;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use Silverstripe\SiteConfig\SiteConfig;
use Azt3k\SS\Social\Objects\SocialHelper;
use SilverStripe\Assets\Upload_Validator;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use SilverStripe\AssetAdmin\Forms\UploadField;
use Azt3k\SS\Social\DataObjects\OEmbedCacheItem;
use Azt3k\SS\Social\DataObjects\PublicationTweet;
use Azt3k\SS\Social\Controllers\PostToSocialMedia;
use Azt3k\SS\Social\DataObjects\PublicationFBUpdate;
use Azt3k\SS\Social\DataObjects\PublicationInstagramUpdate;

/**
 * @todo need reconcile removals in both directions
 * @todo remove PublicationFBUpdateID && PublicationTweetID as they aren't really needed any more - if testing for post just call $this->owner->PublicationTweets()->count()
 */
class SocialMediaPageExtension extends DataExtension {

    protected $justPosted = false;

    private static $db = array(
        'LastPostedToSocialMedia'       => 'Datetime',
        'PublicationFBUpdateID'         => 'Varchar(255)',
        'PublicationTweetID'            => 'Varchar(255)',
        'MetaTitle'                     => 'Varchar(255)',
        'MetaKeywords'                  => 'Varchar(255)',
        'ForceUpdateMode'               => 'Enum(\'Default,Block,Force\')'
    );

    private static $has_many = array(
        'PublicationTweets'             => PublicationTweet::class,
        'PublicationFBUpdates'          => PublicationFBUpdate::class,
        'PublicationInstagramUpdates'   => PublicationInstagramUpdate::class
    );

    private static $has_one = array(
        'PrimaryImage'                  => Image::class,
    );

    private static $defaults = array(
        'ForceUpdateMode'                => 'Default'
    );

    private static $casting = array(
        //'SocialEmbedParser'              => 'HTMLText'
    );

    // Short Code parser
    // -----------------

    /**
     * parses out short codes:
     * [social_embed,service="twitter",url="https://twitter.com/nytimes/status/701590150434967553"]
     * [social_embed,service="facebook",url="https://www.facebook.com/telesurenglish/photos/a.492297374247003.1073741828.479681268841947/791129364363801/"]
     * [social_embed,service="instagram",url="https://www.instagram.com/p/BCEoPpwDw-t/"]use Silverstripe\SiteConfig\SiteConfig;
     * @param [type] $arguments [description]
     * @param [type] $content   [description]
     * @param [type] $parser    [description]
     * @param [type] $tagName   [description]
     */
    public static function SocialEmbedParser($arguments, $content = null, $parser = null, $tagName = null) {
        if ($embed = OEmbedCacheItem::fetch($arguments)) {
            if ($data = $embed->data()) return $data->html;
        }
        return null;
    }

    // Dummy getters - can be overridden on a per project basis to channel specific content to updates
    // ------------------------------------------------------------------------------------------------

    public function AssociatedImage() {
        return false;
    }

    public function SharedContent() {
        return $this->owner->Content;
    }

    public function SharedTitle() {
        return $this->owner->Title;
    }

    public function SharedLink() {
        return $this->owner->AbsoluteLink();
    }

    // Other Methods
    // ------------------------------------------------------------------------------------------------

    public function parseContent($content, $words = null, $allowedTags = '<br>') {
        $br2nl = false;
        if (!$allowedTags || stripos('<br>',$allowedTags) === false) {
            $allowedTags.= '<br>';
            $br2nl = true;
        }
        $str = preg_replace(
            '/<br><br>$/',
            '',
            strip_tags(
                str_replace(
                    array('<p>','</p>'),
                    array('','<br><br>'),
                    preg_replace(
                        '/[\s\t\n ]+/',
                        ' ',
                        $content
                    )
                ),
                $allowedTags
            )
        );
        if ($br2nl) $str = str_replace (array('<br>','<br/>','<br />'),"\n", $str);
        return $words ? AbcStr::get($str)->limitWords($words)->str : $str;
    }

    public function getFieldsToPush() {
        $content = $this->parseContent($this->owner->SharedContent(), 25, null);
        $image = $this->owner->AssociatedImage() ? $this->owner->AssociatedImage()->getAbsoluteURL() : null ;
        return array(
            'message'        => $content,
            'name'           => $this->owner->SharedTitle(),
            'link'           => $this->owner->SharedLink(),
            'description'    => $content,
            'picture'        => $image
        );
    }

    public function updateCMSFields(FieldList $fields) {

        // clean up
        $fields->removeByName('MetaDescription');
        $fields->removeByName('ExtraMeta');
        $fields->removeByName('Metadata');

        // Push updates stuff
        $conf = SiteConfig::current_site_config();
        if ($conf->TwitterPushUpdates || $conf->FacebookPushUpdates) {
            $fields->addFieldToTab('Root.SocialMedia', new ReadonlyField('LastPostedToSocialMedia', 'Last Posted To Social Media'));
            $fields->addFieldToTab('Root.SocialMedia', new DropdownField('ForceUpdateMode', 'Update Mode', singleton(get_class($this->owner))->dbObject('ForceUpdateMode')->enumValues()));
        }

        // Add Meta Fields
        $fields->addFieldsToTab(
            'Root.Meta',
            [
                $metaFieldTitle = TextField::create('MetaTitle'),
                $metaFieldKeywords = TextareaField::create('MetaKeywords'),
                $metaFieldDesc = new TextareaField("MetaDescription", $this->owner->fieldLabel('MetaDescription')),
                $metaFieldExtra = new TextareaField("ExtraMeta", $this->owner->fieldLabel('ExtraMeta'))
            ]
        );

        // Help text for MetaData on page content editor
        $metaFieldDesc
            ->setRightTitle(
                _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.METADESCHELP',
                    "Search engines use this content for displaying search results (although it will not influence their ranking)."
                )
            )
            ->addExtraClass('help');
        $metaFieldExtra
            ->setRightTitle(
                _t(
                    'SilverStripe\\CMS\\Model\\SiteTree.METAEXTRAHELP',
                    "HTML tags for additional meta information. For example <meta name=\"customName\" content=\"your custom content here\" />"
                )
            )
            ->addExtraClass('help');

        // images
        $iValidator = new Upload_Validator;
        $iValidator->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
        $fields->AddFieldsToTab(
            'Root.Images',
            [
                UploadField::create('PrimaryImage')->setValidator($iValidator),
            ]
        );
    }

    public function onAfterPublish() {

        if ($this->owner->ClassName != Tweet::class && $this->owner->ClassName != FBUpdate::class && $this->owner->ClassName != InstagramUpdate::class) {

            // define the date window for repost
            $dateWindow 	= 60 * 60 * 24 * 30; // 30 days
            $lastPost 		= strtotime($this->owner->LastPostedToSocialMedia);
            $time 			= time();
            $embargoExpired = false;

            if ($time > $lastPost + $dateWindow) $embargoExpired = true;
            if (empty($this->justPosted)) $this->justPosted = false;

            if (
				(
					$embargoExpired ||
					!$this->owner->PublicationFBUpdateID ||
					!$this->owner->PublicationInstagramUpdateID ||
					!$this->owner->PublicationTweetID ||
					$this->owner->ForceUpdateMode == 'Force'
				) &&
				$this->owner->ForceUpdateMode != 'Block'
			) {

                // what are we posting to
                $postTo = array();
                if ((!$this->owner->PublicationFBUpdateID || $embargoExpired || $this->owner->ForceUpdateMode == 'Force') && !$this->justPosted) $postTo[] = 'facebook';
                if ((!$this->owner->PublicationTweetID || $embargoExpired || $this->owner->ForceUpdateMode == 'Force') && !$this->justPosted) $postTo[] = 'twitter';

                // set the last post date
                $this->owner->LastPostedToSocialMedia = date('Y-m-d H:i:s');

                // make the posts
                $social = new PostToSocialMedia();
                $ids = $social->sendToSocialMedia($this->getFieldsToPush(), $postTo);

                // update the owner
                if (!empty($ids['facebook']))    $this->owner->PublicationFBUpdateID = $ids['facebook'];
                if (!empty($ids['twitter']))    $this->owner->PublicationTweetID = $ids['twitter'];

                // save if we have new data
                $save = false;
                if (in_array('twitter',$postTo) && !empty($ids['twitter'])) {
                    $save = true;
                    $pubTweet = new PublicationTweet;
                    $pubTweet->TweetID = $ids['twitter'];
                    $pubTweet->PageID = $this->owner->ID;
                    $pubTweet->write();
                }
                if (in_array('facebook',$postTo) && !empty($ids['facebook'])) {
                    $save = true;
                    $pubUpdate = new PublicationFBUpdate;
                    $pubUpdate->FBUpdateID = $ids['facebook'];
                    $pubUpdate->PageID = $this->owner->ID;
                    $pubUpdate->write();
                }
                if ($save) {
                    $this->justPosted = true;
                    $this->owner->write();
                    $this->owner->doPublish();
                }

            }
        }

        return;
    }

    public function ImageWithFallback() {

        // get site conf
        $conf = SiteConfig::current_site_config();

        // img
        $img = in_array($this->owner->ClassName, [Tweet::class, FBUpdate::class, InstagramUpdate::class])
            ? $this->owner->UpdateImage()
            : $this->owner->PrimaryImage();

        // fall back to site default
        if (!$img && empty($img->ID)) $img = $conf->DefaultImage();

        // return an image if we can
        return $img && !empty($img->ID) ? $img : null;
    }

    /**
     * Returns a share url for the current page
     * @param string $service the service you want a url for
     * @return string the share url
     */
    public function ShareUrl($service = 'facebook') {

        $conf       = SiteConfig::current_site_config();
        $img        = ($img = $this->owner->ImageWithFallback()) ? rawurlencode($img->AbsoluteURL) : null;
        $share_url  = rawurlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $title      = rawurlencode($this->owner->Title);
        $src        = rawurlencode($conf->Title);
        $rawSummary = $this->owner->MetaDescription ? $this->owner->MetaDescription : $this->owner->obj('Content')->FirstParagraph();
        $summary    = rawurlencode($rawSummary);

        switch ($service) {
            case 'facebook' :
                $url = 'http://www.facebook.com/sharer/sharer.php?m2w&u=' . $share_url;
                break;

            case 'twitter' :
                $url = 'http://twitter.com/intent/tweet?url=' . $share_url .
                                                     '&text=' . $summary .
                                                 '&hashtags=' . $src;
                break;

            case 'linked_in' :
                $url = 'http://www.linkedin.com/shareArticle?mini=true&url=' . $share_url .
                                                                   '&title=' . $title .
                                                                 '&summary=' . $summary .
                                                                  '&source=' . $src;
                break;

            case 'google_plus' :
                $url = 'https://plus.google.com/share?url=' . $share_url;
                break;

            case 'wei_bo' :
                $url = 'http://service.weibo.com/share/share.php?ralateUid=&language=zh_cn&url=' . $share_url .
                                                                               '&appkey=&title=' . $title .
                                                                                         '&pic=' . $img;
                break;
        }

        return $url;
    }


    /**
     * Returns some meta data for the template
     * @param string $key the meta data you want
     * @return string the value for the key passed in
     */
    public function Meta($key)
    {
        $map = $this->MetaMap();
        return $map[$key];
    }

    /**
     * Returns some meta data for the template
     * @return array a map of meta values
     */
    public function MetaMap()
    {
        $conf = SiteConfig::current_site_config();
        return [
            'Title' =>  ($this->owner->MetaTitle ? $this->owner->MetaTitle : $this->owner->Title) . ' | ' . $conf->Title,
            'Keywords' =>  $this->owner->MetaKeywords ? $this->owner->MetaKeywords : $conf->MetaKeywords,
            'Description' =>  $this->owner->MetaDescription ? $this->owner->MetaDescription : $conf->MetaDescription,
            'SiteName' =>  $conf->Title,
            'Link' =>  $this->owner->AbsoluteLink(),
            'Image' =>  $this->owner->ImageWithFallback() ? $this->owner->ImageWithFallback()->AbsoluteURL : null,
            'TwitterCreator' =>  '@' . $conf->TwitterUsername,
            'TwitterPublisher' =>  '@' . $conf->TwitterUsername,
            'TimeModified' =>  $this->owner->LastEdited,
            'TimeCreated' =>  $this->owner->Created
        ];
    }
}
