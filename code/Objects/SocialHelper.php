<?php

namespace Azt3k\SS\Social\Objects;

use SilverStripe\Core\Extensible;
use SilverStripe\Core\Config\Config;
use Azt3k\SS\Social\Objects\SocialHelper;
use Facebook\Facebook;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Config\Configurable;

/**
 * @author AzT3k
 */
class SocialHelper {

    use Extensible, Injectable, Configurable;

    /**
     * generates a url to the current page
     * @param  boolean $dropqs [description]
     * @return string          [description]
     */
    public static function php_self($dropqs = true) {

        // figure out what the protocol is
        $protocol = 'http';

        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
            $protocol = 'https';

        elseif (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443'))
            $protocol = 'https';

        // figure out what the server name is
        $serverName = empty($_SERVER['SERVER_NAME']) ? 'localhost' : $_SERVER['SERVER_NAME'];

        // figure out what the port is
        $port = empty($_SERVER['SERVER_PORT']) ? 80 : $_SERVER['SERVER_PORT'];

        // build the uri
        $url    = sprintf('%s://%s%s', $protocol, $serverName, $_SERVER['REQUEST_URI']);
        $parts  = parse_url($url);
        $port   = $port;
        $scheme = $parts['scheme'];
        $host   = $parts['host'];
        $path   = @$parts['path'];
        $qs     = @$parts['query'];
        $port or $port = ($scheme == 'https') ? '443' : '80';

        if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80'))
            $host = "$host:$port";

        $url = $scheme. '://' . $host . $path;

        if (!$dropqs) return "{$url}?{$qs}";
        else return $url;
    }

    public static function fb_access_token() {

        $conf = SiteConfig::current_site_config();
        $token = null;

        // get page token
        $token = $conf->FacebookPageAccessToken;

        // if that failed get the user token
        if (!$token) $token = $conf->FacebookUserAccessToken;

        // if the page and user token are bad then get an app access token
        if (!$token) {

            $facebook = new Facebook(array(
                'app_id'  => $conf->FacebookAppId,
                'app_secret' => $conf->FacebookAppSecret
            ));

            $url = '/oauth/access_token' .
                    '?client_id=' . $conf->FacebookAppId .
                    '&client_secret=' . $conf->FacebookAppSecret .
                    '&grant_type=client_credentials';

            //valueToGetPastGuardConditon
            //we replace the access token later
            $res = $facebook->sendRequest('get', $url, [], 'valueToGetPastGuardConditon')->getDecodedBody();
            $token = $res['access_token'];
        }

        return $token;

    }

    /**
     * generates page links for various services
     * @param  string $id      [description]
     * @param  string $service [description]
     * @param  string $type    [description]
     * @return string          [description]
     */
    public static function link($id, $service, $type = 'user') {
        switch ($service) {
            case 'facebook':
                if ($type == 'user') return 'https://www.facebook.com/' . $id;
                if ($type == 'page') return 'https://www.facebook.com/pages/' . $id;

            case 'twitter':
                return 'https://twitter.com/' . $id;

            case 'instagram':
                return 'https://instagram.com/' . $id;

        }
        return null;
    }

}
