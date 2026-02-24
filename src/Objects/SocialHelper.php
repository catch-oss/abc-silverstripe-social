<?php

namespace Azt3k\SS\Social\Objects;

use SilverStripe\Core\Extensible;
use JanuSoftware\Facebook\Facebook;
use SilverStripe\SiteConfig\SiteConfig;
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
    public static function php_self(bool $dropqs = true): string
    {

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

    public static function fb_access_token(): mixed
    {

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
     * Maximum allowed size for downloaded images (10MB)
     */
    private static int $max_image_size = 10 * 1024 * 1024;

    /**
     * Valid image magic byte signatures
     */
    private static array $image_signatures = [
        "\xFF\xD8\xFF"       => 'image/jpeg',
        "\x89PNG\r\n\x1a\n"  => 'image/png',
        "GIF87a"             => 'image/gif',
        "GIF89a"             => 'image/gif',
        "RIFF"               => 'image/webp',
    ];

    /**
     * Validates that binary data starts with a known image file signature.
     */
    public static function isValidImageData(string $data): bool
    {
        foreach (static::$image_signatures as $signature => $type) {
            if (str_starts_with($data, $signature)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Downloads an image from a URL with size limit and content validation.
     * Returns the image data on success, or false on failure.
     */
    public static function downloadImage(string $url): string|false
    {
        $context = stream_context_create(['http' => ['timeout' => 30]]);
        $data = @file_get_contents($url, false, $context);

        if ($data === false) {
            return false;
        }

        if (strlen($data) > static::$max_image_size) {
            return false;
        }

        if (!static::isValidImageData($data)) {
            return false;
        }

        return $data;
    }

    /**
     * generates page links for various services
     * @param  string $id      [description]
     * @param  string $service [description]
     * @param  string $type    [description]
     * @return string          [description]
     */
    public static function link(?string $id, string $service, string $type = 'user'): ?string
    {
        if (empty($id)) return null;

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
