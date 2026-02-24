<?php

namespace Azt3k\SS\Social\Extensions;

use Azt3k\SS\Social\Objects\SocialHelper;
use SilverStripe\Forms\FieldList;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Core\Extension;
use SilverStripe\AssetAdmin\Forms\UploadField;
use Azt3k\SS\Social\Controllers\FBAuthenticator;
use Azt3k\SS\Social\Controllers\TwitterAuthenticator;
use Azt3k\SS\Social\Controllers\InstagramAuthenticator;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;
class SocialMediaConfig extends Extension {

    private static $db = array(

        'FacebookAppId'                     => 'Varchar(255)',
        'FacebookAppSecret'                 => 'Varchar(255)',
        'FacebookUserId'                    => 'Varchar(255)',
        'FacebookUserAccessToken'           => 'Varchar(255)',
        'FacebookUserAccessTokenExpires'    => 'Datetime',
        'FacebookPageId'                    => 'Varchar(255)',
        'FacebookPageAccessToken'           => 'Varchar(255)',
        'FacebookPageAccessTokenExpires'    => 'Datetime',
        'FacebookPageLink'                  => 'Varchar(255)',
        'FacebookPageFeedType'              => 'Enum(\'feed,posts,tagged,promotable_posts\',\'feed\')',
        'FacebookPushUpdates'               => 'Boolean',
        'FacebookPullUpdates'               => 'Boolean',

        'TwitterConsumerKey'                => 'Varchar(255)',
        'TwitterConsumerSecret'             => 'Varchar(255)',
        'TwitterOAuthToken'                 => 'Varchar(255)',
        'TwitterOAuthTokenExpires'          => 'Datetime',
        'TwitterOAuthSecret'                => 'Varchar(255)',
        'TwitterUsername'                   => 'Varchar(255)',
        'TwitterPushUpdates'                => 'Boolean',
        'TwitterPullUpdates'                => 'Boolean',

        'InstagramApiKey'                   => 'Varchar(255)',
        'InstagramApiSecret'                => 'Varchar(255)',
        'InstagramOAuthToken'               => 'Varchar(255)',
        'InstagramOAuthTokenExpires'        => 'Datetime',
        'InstagramUsername'                 => 'Varchar(255)',
        'InstagramUserId'                   => 'Varchar(255)',
        'InstagramPushUpdates'              => 'Boolean',
        'InstagramPullUpdates'              => 'Boolean',
    );

    private static $has_one = array(
        'DefaultImage'                      => Image::class,
        'DefaultFBUpdateImage'              => Image::class,
        'DefaultTweetImage'                 => Image::class,
        'DefaultInstagramUpdateImage'       => Image::class,
    );

    public function updateCMSFields(FieldList $fields): void
    {


        // ---------
        // Images
        // ---------

        // Image
        $imageField = new UploadField('DefaultImage', 'DefaultImage');
        $imageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $imageField);

        // Image
        $fbImageField = new UploadField('DefaultFBUpdateImage', 'Default Facebook Image');
        $fbImageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $fbImageField);

        // Image
        $tweetImageField = new UploadField('DefaultTweetImage', 'Default Twitter Image');
        $tweetImageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $tweetImageField);

        // Image
        $instagramImageField = new UploadField('DefaultInstagramUpdateImage', 'Default Instagram Image');
        $instagramImageField->getValidator()->setAllowedExtensions(array('jpg','jpeg','gif','png'));
        $fields->addFieldToTab('Root.Images', $instagramImageField);

        // ---------
        // Facebook
        // ---------

        $fields->addFieldToTab('Root.SocialMedia',    new LiteralField('FacebookHeading',    '<h3>Facebook</h3>'));

        // Validate Page Access Token
        $userValid = $pageValid = false;
        try {
            $pageValid = FBAuthenticator::validate_current_conf('page');
        } catch (\Exception $e) {
            $pageMsg = $e->getMessage();
            $fields->addFieldToTab('Root.SocialMedia',    new LiteralField(
                'FacebookBrokenPageConf',
                '<span style="color:red">Your facebook page configuration is broken (' . $pageMsg . ')</span>'
            ));
        }

        // Validate User Access Token
        try {
            $userValid = FBAuthenticator::validate_current_conf('user');
        } catch (\Exception $e) {
            $userMsg = $e->getMessage();
            $fields->addFieldToTab('Root.SocialMedia',    new LiteralField(
                'FacebookBrokenUserConf',
                '<p style="color:red">Your facebook user configuration is broken (' . $userMsg . ')</p>'
            ));

        }

        $fields->addFieldsToTab(
            'Root.SocialMedia',
            array(
                new LiteralField('FacebookAppLink', '<p>Manage your apps here: <a href="https://developers.facebook.com/apps/">https://developers.facebook.com/apps/</a></p>'),
                new LiteralField('FacebookIDLink', '<p>Find your Facebook IDs here: <a href="http://findmyfbid.com/">http://findmyfbid.com/</a></p>'),
                new TextField('FacebookAppId', 'Facebook App Id'),
                new TextField('FacebookAppSecret', 'Facebook App Secret'),
                new TextField('FacebookUserId', 'Facebook User Id'),
                new TextField('FacebookPageId', 'Facebook Page Id'),
                new DropdownField('FacebookPageFeedType', 'Facebook Page Feed Type', $this->owner->dbObject('FacebookPageFeedType')->enumValues()),
                new CheckboxField('FacebookPushUpdates', 'Push publication updates to authorised Facebook account'),
                new CheckboxField('FacebookPullUpdates', 'Pull publication updates from authorised Facebook account'),
                new LiteralField('FacebookUserData', '<h4>Facebook User</h4>'),
                new LiteralField('FacebookUserLink', '<p><a target="_blank" href="' . $this->FacebookUserLink(). '">' . $this->owner->FacebookUserId . '</a></p>'),
                new LiteralField('FacebookUserAccessToken', '<p>Facebook User Access Token</p><p>'.($this->owner->FacebookUserAccessToken ? $this->owner->FacebookUserAccessToken.' <a href="/FBAuthenticator/purge" target="_blank">Wipe</a>' : '<a href="/FBAuthenticator" target="_blank">Authenticate</a>').'</p>'),
                new LiteralField('FacebookPageData', '<h4>Facebook Page</h4>'),
                new LiteralField('FacebookPageLink', '<p><a target="_blank" href="' . $this->FacebookPageLink(). '">' . $this->owner->FacebookPageId . '</a></p>'),
                new LiteralField('FacebookPageAccessToken', '<p>Facebook Page Access Token</p><p>'.($this->owner->FacebookPageAccessToken ? $this->owner->FacebookPageAccessToken.' <a href="/FBAuthenticator/purge" target="_blank">Wipe</a>' : '<a href="/FBAuthenticator" target="_blank">Authenticate</a>').'</p>'),
            )
        );

        // ---------
        // Twitter
        // ---------

        $fields->addFieldToTab('Root.SocialMedia',    new LiteralField('TwitterHeading', '<br><h3>Twitter</h3>'));

        // user
        try {
            $twitterValid = TwitterAuthenticator::validate_current_conf();
        } catch (\Exception $e) {
            $twitterMsg = $e->getMessage();
            $fields->addFieldToTab('Root.SocialMedia',    new LiteralField(
                'TwitterBrokenConf',
                '<p style="color:red">Your twitter configuration is broken</p>'
            ));

        }

        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('TwitterAppLink', '<p>Manage your apps here: <a href="https://apps.twitter.com/">https://apps.twitter.com/</a></p>'));
        $fields->addFieldToTab('Root.SocialMedia', new TextField('TwitterConsumerKey', 'Twitter Consumer Key'));
        $fields->addFieldToTab('Root.SocialMedia', new TextField('TwitterConsumerSecret', 'Twitter Consumer Secret'));

        // only add the username field if we don't have an auth token
        if (!$this->owner->TwitterOAuthToken)
            $fields->addFieldToTab('Root.SocialMedia', new TextField('TwitterUsername', 'Twitter Username (optional)'));

        $fields->addFieldToTab('Root.SocialMedia', new CheckboxField('TwitterPushUpdates', 'Push publication updates to authorised Twitter account'));
        $fields->addFieldToTab('Root.SocialMedia', new CheckboxField('TwitterPullUpdates', 'Pull publication updates from authorised Twitter account'));

        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('TwitterUserData', '<h4>Twitter User</h4>'));
        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('TwitterUserLink', '<p><a href="' . $this->TwitterPageLink(). '">' . $this->owner->TwitterUsername . '</a></p>'));
        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('TwitterOAuthToken', '<p>Twitter OAuth Token</p><p>'.($this->owner->TwitterOAuthToken ? $this->owner->TwitterOAuthToken.' <a href="/TwitterAuthenticator?wipe=1" target="_blank">Wipe</a>' : '<a href="/TwitterAuthenticator?start=1" target="_blank">Authenticate</a>').'</p>'));
        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('TwitterOAuthSecret', '<p>Twitter OAuth Secret</p><p>'.($this->owner->TwitterOAuthSecret ? $this->owner->TwitterOAuthSecret.' <a href="/TwitterAuthenticator?wipe=1" target="_blank">Wipe</a>' : '<a href="/TwitterAuthenticator?start=1" target="_blank">Authenticate</a>').'</p>'));

        // ---------
        // Instagram
        // ---------

        $fields->addFieldToTab('Root.SocialMedia',    new LiteralField('InstagramHeading', '<br><h3>Instagram</h3>'));

        // user
        try {
            $instagramValid = InstagramAuthenticator::validate_current_conf();
        } catch (\Exception $e) {
            $instagramMsg = $e->getMessage();
            $fields->addFieldToTab('Root.SocialMedia',    new LiteralField(
                'InstagramBrokenConf',
                '<p style="color:red">Your instagram configuration is broken</p>'
            ));

        }

        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('InstagramAppLink', '<p>Manage your apps here: <a href="https://developers.facebook.com/apps/">https://developers.facebook.com/apps/</a></p>'));
        $fields->addFieldToTab('Root.SocialMedia', new TextField('InstagramApiKey', 'Instagram App ID'));
        $fields->addFieldToTab('Root.SocialMedia', new TextField('InstagramApiSecret', 'Instagram App Secret'));

        // only add the username field if we don't have an auth token
        if (!$this->owner->InstagramOAuthToken) {
            $fields->addFieldToTab('Root.SocialMedia', new TextField('InstagramUsername', 'Instagram Username'));
            $fields->addFieldToTab('Root.SocialMedia', new TextField('InstagramUserId', 'Instagram User ID'));
        }

        $fields->addFieldToTab('Root.SocialMedia', new CheckboxField('InstagramPushUpdates', 'Push publication updates to authorised Instagram account'));
        $fields->addFieldToTab('Root.SocialMedia', new CheckboxField('InstagramPullUpdates', 'Pull publication updates from authorised Instagram account'));

        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('InstagramUserData', '<h4>Instagram User</h4>'));
        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('InstagramUserLink', '<p><a target="_blank" href="' . $this->InstagramPageLink(). '">' . $this->owner->InstagramUsername . '(' . $this->owner->InstagramUserId . ')</a></p>'));
        $fields->addFieldToTab('Root.SocialMedia', new LiteralField('InstagramOAuthToken', '<p>OAuth Token</p><p>'.($this->owner->InstagramOAuthToken ? $this->owner->InstagramOAuthToken.' <a href="/InstagramAuthenticator?wipe=1" target="_blank">Wipe</a>' : '<a href="/InstagramAuthenticator?start=1" target="_blank">Authenticate</a>').'</p>'));

    }

    public function InstagramPageLink(): ?string
    {
        return SocialHelper::link($this->owner->InstagramUsername, 'instagram');
    }

    public function TwitterPageLink(): ?string
    {
        return SocialHelper::link($this->owner->TwitterUsername, 'twitter');
    }

    public function FacebookUserLink(): ?string
    {
        return SocialHelper::link($this->owner->FacebookUserId, 'facebook');
    }

    public function FacebookPageLink(): ?string
    {
        return SocialHelper::link($this->owner->FacebookPageId, 'facebook', 'page');
    }
}
