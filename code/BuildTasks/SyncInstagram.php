<?php

namespace Azt3k\SS\Social\BuildTasks;

use MetzWeb\Instagram\Instagram;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\DataObjects\PublicationInstagramUpdate;
use Azt3k\SS\Social\Objects\SocialHelper;
use Azt3k\SS\Classes\DataObjectHelper;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;


// need to update to this
// https://developers.facebook.com/docs/instagram-basic-display-api - get posts
// https://developers.facebook.com/docs/instagram-api - make posts

class SyncInstagram extends BuildTask implements CronTask{

    protected static $conf_instance;
    protected static $instagram_instance;
    protected $conf;
    protected $instagram;
    protected $errors = array();
    protected $messages = array();

    public function __construct() {

        $this->conf = static::get_conf();
        $this->instagram = static::get_instagram();

        parent::__construct();
    }

    protected function flushStatements()
    {
        $conn = \SilverStripe\ORM\DB::get_conn();
        $connector = $conn->getConnector();
        $connector->flushStatements();
    }

    public function getSchedule() {
        return "*/5 * * * *";
    }

    public static function get_conf() {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public static function get_instagram() {
        if (!static::$instagram_instance) {
            $conf = static::get_conf();
            static::$instagram_instance = new Instagram(array(
                'apiKey'      => $conf->InstagramApiKey,
                'apiSecret'   => $conf->InstagramApiSecret,
                'apiCallback' => SocialHelper::php_self(),
            ));
        }

        return static::$instagram_instance;
    }

    public function init() {

        if (method_exists(parent::class,'init')) parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }

        if (!$this->conf || !$this->instagram) $this->__construct();

    }

    public function process() {
        $this->init();
        $this->run();
    }

    public function run($request = null) {

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        // output
        echo $eol . $eol . 'Syncing...' . $eol . $eol;
        flush();
        @ob_flush();

        if (!$this->conf->InstagramPullUpdates) {
            echo 'Sync disabled' . $eol . $eol;
            return;
        }

        // flush first to avoid hitting prepared statements cap
        $this->flushStatements();

        // grab the most recent InstagramUpdate
        $lastInstagramUpdate = DataObject::get_one(InstagramUpdate::class);

        // if there was no last InstagramUpdate we need to go into initial population
        $initPop = $lastInstagramUpdate ? false : true ;

        // get the first 90
        $updates = $this->instagram->getUserMedia($this->conf->InstagramUserId, 90);

        // die(print_r($updates, 1));

        // only proceed if the request was valid
        if ((!empty($updates->meta) && $updates->meta->code == 200)) {

            if (!empty($updates->data)) {

                // process the response
                $this->processResponse($updates);

                // check if we need to do an initial population
                if ($initPop) {

                    //output
                    echo "<br />\n<br />\nDoing initial Population<br />\n<br />\n";
                    flush();
                    @ob_flush();

                    // keep going until we hit a problem
                    while ($updates = $this->instagram->pagination($updates)) {

                        if ((!empty($updates->meta) && $updates->meta->code == 200)) {
                            if (!empty($updates->data)) $this->processResponse($updates);
                        }
                        else die(print_r($updates ,1));

                    }

                    // output
                    echo "Finished";
                    flush();
                    @ob_flush();
                }

            } else {

                // output
                echo "No hits <br />\n<br />\n";
                flush();
                @ob_flush();

            }
        }

        else die(print_r($updates ,1));

    }

    public function processResponse(\stdClass $resp) {

        $noNew = true;

        foreach ($resp->data as $data) {
            if (!$savedInstagramUpdate = DataObject::get_one(InstagramUpdate::class, "UpdateID='" . $data->id . "'")) {
                if (!$pubInstagramUpdate = DataObject::get_one(PublicationInstagramUpdate::class, "InstagramUpdateID='" . $data->id . "'")) {

                    // push output
                    echo "Adding InstagramUpdate " . $data->id . "<br />\n";
                    flush();
                    @ob_flush();

                    // create the InstagramUpdate Page
                    $update = new InstagramUpdate;

                    // try to update
                    if ($update->updateFromUpdate($data)) {
                        if ($update->write() && $update->doRestoreToStage() && $update->doPublish()) {
                            echo 'Successfully created' . $update->Title ."<br />\n";
                        } else {
                            die('Failed to Publish ' . $update->Title);
                        }
                    }

                    // set no new flag
                    $noNew = false;

                } else {

                    // push output
                    echo "InstagramUpdate ".$data->id." came from the website<br />\n";
                    flush();
                    @ob_flush();

                }

            } else {

                // this should only happen during initial population because we should have only got in InstagramUpdates that are newer than x

                // push output
                echo "Already added InstagramUpdate ".$data->id."<br />\n";
                flush();
                @ob_flush();

            }
        }

        return $noNew;

    }

}
