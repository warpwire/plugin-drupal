<?php

namespace Drupal\warpwire\Lib;

/**
 * Class representing an instance of a Warpwire asset URL
 */
class WarpwireAssetUrl
{
    // Constants=
    public static $ALLOWED_QUERY_PARAMS = ['audio_only', 'autoplay', 'cc_load_policy', 'controls', 'embed_nonce', 'embed_signature', 'end', 'seek_mode', 'share', 'start', 'title'];
    public static $SHORTCODE_REGEX = '/^\/w\/([\w\-]+)\/?$/';

    // URL
    public string $url;

    // Derived from URL
    public string $host;
    public string $site_url;
    public string $lti_url;
    public string $shortcode;
    public string $asset_url;
    public string $oembed_url;

    // Parsed query parameters
    public array $query_params = [];

    // Flags derived during parse
    private bool $has_valid_site = FALSE;
    private bool $has_valid_asset = FALSE;

    public function __construct($url)
    {

        $this->url = trim($url);

        if (filter_var($this->url, FILTER_VALIDATE_URL) === FALSE) {
            return;
        }

        $parts = parse_url($url);

        if (!isset($parts['host']) || empty($parts['host'])) {
            return;
        }

        // If there is a host, then URL is not empty
        $this->has_valid_site = TRUE;

        // Site-level values
        $this->host = $parts['host'];
        $this->site_url = 'https://' . $parts['host'];
        $this->lti_url = 'https://' . $parts['host'] . '/api/ltix/';

        // Parse the path to extract the shortcode
        $matches = [];
        if (!isset($parts['path']) || !preg_match(static::$SHORTCODE_REGEX, $parts['path'], $matches)) {
            // If there is not a valid path, simply return the host
            return;
        }

        // If there is a shortcode, then URL is valid
        $this->has_valid_asset = TRUE;

        // Asset-level values
        $this->shortcode = $matches[1];
        $this->asset_url = 'https://' . $parts['host'] . '/w/' . $this->shortcode . '/';
        $this->oembed_url = 'https://' . $parts['host'] . '/api/oembed/?url=' . urlencode($this->asset_url) . '&format=json';

        // Parse the query params
        if (isset($parts['query'])) {
            $query = [];
            parse_str($parts['query'], $query);
            $this->query_params = WarpwireUtils::arraySelect($query, static::$ALLOWED_QUERY_PARAMS);
        }
    }

    /**
     * The input is a valid URL and includes a host
     * 
     * @return bool 
     */
    public function isValidSiteUrl(): bool
    {
        return $this->has_valid_site;
    }

    /**
     * The input is a valid URL and includes a host and a shortcode
     * 
     * @return bool 
     */
    public function isValidAssetUrl(): bool
    {
        return $this->has_valid_asset;
    }
}
