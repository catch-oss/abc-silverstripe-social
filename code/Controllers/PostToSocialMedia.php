<?php

namespace Azt3k\SS\Social\Controllers;

use SilverStripe\Control\Controller;
use JanuSoftware\Facebook\Facebook;
use Silverstripe\SiteConfig\SiteConfig;
use Azt3k\SS\Social\Controllers\TwitterAuthenticator;
use Azt3k\SS\Social\Controllers\FBAuthenticator;
use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use themattharris\tmhOAuth;

/**
 * @author AzT3k
 */
class PostToSocialMedia extends Controller
{

    protected static $conf;

    public function __construct()
    {

        static::$conf = SiteConfig::current_site_config();

        parent::__construct();
    }

    /**
     * @todo actually validate the configuration - will need to create a class extened from controller for authenticating / validating the configuration refer to FBAuthenticator
     * @return boolean
     */
    public function confirmTwitterAccess()
    {

        if (!static::$conf->TwitterPushUpdates) return false;

        if (
            static::$conf->TwitterConsumerKey &&
            static::$conf->TwitterConsumerSecret &&
            static::$conf->TwitterOAuthToken &&
            static::$conf->TwitterOAuthSecret
        ) {
            try {
                TwitterAuthenticator::validate_current_conf();
                return true;
            } catch (Exception $e) {
                static::$conf->TwitterOAuthToken = null;
                static::$conf->TwitterOAuthSecret = null;
                static::$conf->write();
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean
     */
    public function confirmFacebookAccess()
    {

        if (!static::$conf->FacebookPushUpdates) return false;

        try {
            FBAuthenticator::validate_current_conf('page');
        } catch (Exception $e) {
            static::$conf->FacebookPageAccessToken = null;
            static::$conf->write();
            return false;
        }

        try {
            FBAuthenticator::validate_current_conf('user');
        } catch (Exception $e) {
            static::$conf->FacebookUserAccessToken = null;
            static::$conf->write();
            return false;
        }

        return true;
    }

    /**
     *
     * @param array $data
     * @param array $services
     */
    public function sendToSocialMedia(array $data, array $services = array('facebook', 'twitter'))
    {

        // init output
        $ids = array(
            'facebook'    => null,
            'twitter'    => null
        );

        // Facebook
        if (in_array('facebook', $services) && $this->confirmFacebookAccess()) {

            $facebook = new Facebook(array(
                'appId'  => static::$conf->FacebookAppId,
                'secret' => static::$conf->FacebookAppSecret,
                'default_graph_version' => 'v15.0'
            ));

            $facebook->setDefaultAccessToken(static::$conf->FacebookPageAccessToken);
            try {

                $post_id = $facebook->post("/" . static::$conf->FacebookPageId . "/feed", $data, static::$conf->FacebookPageAccessToken);

                // @todo: don't think a post ID is returned here, no ids are no the response class
                // need another approach here
                $ids['facebook'] = $post_id['id'];
            } catch (Exception $e) {

                Injector::inst()
                    ->get(LoggerInterface::class)
                    ->error('Error ' . $e->getCode() . ' : ' . $e->getFile() . ' Line ' . $e->getLine() . ' : ' . $e->getMessage() . "\n" . 'BackTrace: ' . "\n" . $e->getTraceAsString());
            }
        }

        // Twitter
        if (in_array('twitter', $services) && $this->confirmTwitterAccess()) {

            $connection = new tmhOAuth(array(
                'consumer_key'        => static::$conf->TwitterConsumerKey,
                'consumer_secret'    => static::$conf->TwitterConsumerSecret,
                'user_token'        => static::$conf->TwitterOAuthToken,
                'user_secret'        => static::$conf->TwitterOAuthSecret
            ));

            $tweet = $data['name'] . ": " . $data['link'];
            $code = $connection->request('POST', $connection->url('1.1/statuses/update'), array('status' => $tweet));

            if ($code == 200) {
                $data = json_decode($connection->response['response']);
                $ids['twitter'] = $data->id_str;
            }
        }

        return $ids;
    }
}
