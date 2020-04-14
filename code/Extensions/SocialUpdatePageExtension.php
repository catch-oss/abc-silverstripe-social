<?php

namespace Azt3k\SS\Social\Extensions;

use Azt3k\SS\Social\SiteTree\Tweet;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use SilverStripe\ORM\DataExtension;
use Silverstripe\SiteConfig\SiteConfig;

class SocialUpdatePageExtension extends DataExtension {

    public function UpdateType() {
        switch ($this->owner->ClassName) {
            case Tweet::class:           return 'Twitter';
            case FBUpdate::class:        return 'Facebook';
            case InstagramUpdate::class: return 'Instagram';
        }
    }

    public function UpdateImage() {

        $conf = SiteConfig::current_site_config();

        switch ($this->owner->ClassName) {
            case Tweet::class:
                return $this->owner->PrimaryImageID
                    ? $this->owner->PrimaryImage()
                    : $conf->DefaultTweetImage();
            case FBUpdate::class:
                return $this->owner->PrimaryImageID
                    ? $this->owner->PrimaryImage()
                    : $conf->DefaultFBUpdateImage();
            case InstagramUpdate::class:
                return $this->owner->PrimaryImageID
                    ? $this->owner->PrimaryImage()
                    : $conf->DefaultInstagramUpdateImage();
        }
    }
}
