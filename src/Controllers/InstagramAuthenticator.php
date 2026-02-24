<?php

namespace Azt3k\SS\Social\Controllers;

use Azt3k\SS\Social\Clients\InstagramBasicDisplayClient;
use Azt3k\SS\Social\Objects\SocialHelper;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use Exception;

class InstagramAuthenticator extends Controller {

    private static $allowed_actions = ['index'];

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

    public static function get_conf(): mixed
    {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public static function get_instagram(): InstagramBasicDisplayClient
    {
        if (!static::$instagram_instance) {
            $conf = static::get_conf();
            static::$instagram_instance = new InstagramBasicDisplayClient(
                (string) $conf->InstagramApiKey,
                (string) $conf->InstagramApiSecret,
                SocialHelper::php_self()
            );
        }

        return static::$instagram_instance;
    }

    public static function validate_current_conf(): bool
    {

        $res = static::get_instagram()->getUser((string) static::get_conf()->InstagramOAuthToken);

        if (!empty($res['id'])) {
            return true;
        } else {
            throw new Exception('There was an error: ' . print_r($res, 1));
            return false;
        }
    }

    protected function addError(string $err): void
    {
        $this->errors[] = 'There was an error: ' . $err;
    }

    protected function addMsg(string $msg): void
    {
        $this->messages[] = $msg;
    }

    protected function wipe(): void
    {
        $cnf = static::get_conf();
        $cnf->InstagramOAuthToken = null;
        $cnf->InstagramOAuthTokenExpires = null;
        $cnf->InstagramUsername = null;
        $cnf->InstagramUserId = null;
        $cnf->write();
        header('Location: ' . SocialHelper::php_self());
    }

    // Step 1: Request a temporary token
    protected function request_token(): void
    {
        header("Location: " . static::get_instagram()->getLoginUrl());
        exit;
    }

    // Step 2: This is the code that runs when Instagram redirects the user to the callback. Exchange the temporary token for a permanent access token
    protected function access_token(): void
    {
        $data = static::get_instagram()->exchangeCodeForToken($_REQUEST['code']);
        $this->conf->InstagramOAuthToken = $data['access_token'];
        $this->conf->InstagramOAuthTokenExpires = !empty($data['expires_in'])
            ? date('Y-m-d H:i:s', time() + (int) $data['expires_in'])
            : null;
        $this->conf->InstagramUsername = $data['user']['username'] ?? null;
        $this->conf->InstagramUserId = $data['user']['id'] ?? null;
        $this->conf->write();
    }

    // Step 3: Now the user has authenticated, do something with the permanent token and secret we received
    protected function verify_credentials(): void
    {

        $res = static::get_instagram()->getUser((string) static::get_conf()->InstagramOAuthToken);

        if (!empty($res['id'])) {
            $this->addMsg(
                '<p>Authourised as ' . $res['username'] . '</p>'
            );
        } else {
            $this->addError(print_r($res, 1));
        }
    }

    public function index(): mixed
    {

        // authorise
        $user = Security::getCurrentUser();
        if (!Permission::checkMember($user, 'ADMIN')) return $this->httpError(401, 'You do not have access to the requested content');

        // trigger various modes
        if (isset($_REQUEST['start']))          $this->request_token();
        else if (isset($_REQUEST['code']))      $this->access_token();
        else if (isset($_REQUEST['verify']))    $this->verify_credentials();
        else if (isset($_REQUEST['wipe']))      $this->wipe();

        // verify credentials if available
        if ($this->conf->InstagramOAuthToken && !isset($_REQUEST['verify'])) $this->verify_credentials();

        // display output
        $errMsg = count($this->errors) ? "<p>".implode("<br />", $this->errors)."</p>" : '' ;
        $msgMsg = count($this->messages) ? "<p>".implode("<br />", $this->messages)."</p>" : '' ;

        return '<p>' . $msgMsg . $errMsg . (
            $this->conf->InstagramOAuthToken
                ? 'Do you want to: <ul><li><a href="?verify=1">reverify the credentials?</a></li><li><a href="?wipe=1">wipe them and start again</a></li></ul>'
                : '<a href="?start=1">Click to authorize</a>.'
        ) . '</p>';
    }

}
