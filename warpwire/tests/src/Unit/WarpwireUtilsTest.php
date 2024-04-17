<?php

use \Drupal\Tests\UnitTestCase;
use Drupal\warpwire\Lib\WarpwireAssetUrl;

class WarpwireUtilsTest extends UnitTestCase
{

    public function testSuccessfulParse()
    {
        $input = 'https://support.warpwire.com/w/CwAAAA/?autoplay=1&start=0&end=0&controls=0&seek_mode=watched_only&embed_nonce=nonce&embed_signature=sig';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), true);
        $this->assertEquals($result->isValidAssetUrl(), true);

        $this->assertEquals($result->url, 'https://support.warpwire.com/w/CwAAAA/?autoplay=1&start=0&end=0&controls=0&seek_mode=watched_only&embed_nonce=nonce&embed_signature=sig');
        $this->assertEquals($result->host, 'support.warpwire.com');
        $this->assertEquals($result->site_url, 'https://support.warpwire.com');
        $this->assertEquals($result->lti_url, 'https://support.warpwire.com/api/ltix/');
        $this->assertEquals($result->shortcode, 'CwAAAA');
        $this->assertEquals($result->asset_url, 'https://support.warpwire.com/w/CwAAAA/');
        $this->assertEquals($result->oembed_url, 'https://support.warpwire.com/api/oembed/?url=https%3A%2F%2Fsupport.warpwire.com%2Fw%2FCwAAAA%2F&format=json');
        $this->assertEquals($result->query_params, ['autoplay' => '1', 'start' => '0', 'end' => '0', 'controls' => '0', 'seek_mode' => 'watched_only', 'embed_nonce' => 'nonce', 'embed_signature' => 'sig']);
    }

    public function testSuccessfulParseQueryDefaults()
    {
        $input = 'https://support.warpwire.com/w/CwAAAA/';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), true);
        $this->assertEquals($result->isValidAssetUrl(), true);

        $this->assertEquals($result->url, 'https://support.warpwire.com/w/CwAAAA/');
        $this->assertEquals($result->host, 'support.warpwire.com');
        $this->assertEquals($result->site_url, 'https://support.warpwire.com');
        $this->assertEquals($result->lti_url, 'https://support.warpwire.com/api/ltix/');
        $this->assertEquals($result->shortcode, 'CwAAAA');
        $this->assertEquals($result->asset_url, 'https://support.warpwire.com/w/CwAAAA/');
        $this->assertEquals($result->oembed_url, 'https://support.warpwire.com/api/oembed/?url=https%3A%2F%2Fsupport.warpwire.com%2Fw%2FCwAAAA%2F&format=json');
        $this->assertEquals($result->query_params, []);
    }

    public function testShortcodeWithNumbersAndDashes()
    {
        $input = 'https://support.warpwire.com/w/Cw1-234_/';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), true);
        $this->assertEquals($result->isValidAssetUrl(), true);
        $this->assertEquals($result->shortcode, 'Cw1-234_');
    }

    public function testEmptyUrl()
    {
        $input = '';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), false);
        $this->assertEquals($result->isValidAssetUrl(), false);
    }

    public function testInvalidUrl()
    {
        $input = 'abc';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), false);
        $this->assertEquals($result->isValidAssetUrl(), false);
    }

    public function testHostOnlyUrl()
    {
        $input = 'https://support.warpwire.com/?abc=123';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), true);
        $this->assertEquals($result->isValidAssetUrl(), false);

        $this->assertEquals($result->url, 'https://support.warpwire.com/?abc=123');
        $this->assertEquals($result->host, 'support.warpwire.com');
        $this->assertEquals($result->site_url, 'https://support.warpwire.com');
        $this->assertEquals($result->lti_url, 'https://support.warpwire.com/api/ltix/');
    }

    public function testParseInvalidShortcode()
    {
        $input = 'https://support.warpwire.com/cool-video/';
        $result = new WarpwireAssetUrl($input);

        $this->assertEquals($result->isValidSiteUrl(), true);
        $this->assertEquals($result->isValidAssetUrl(), false);

        $this->assertEquals($result->url, 'https://support.warpwire.com/cool-video/');
        $this->assertEquals($result->host, 'support.warpwire.com');
        $this->assertEquals($result->site_url, 'https://support.warpwire.com');
        $this->assertEquals($result->lti_url, 'https://support.warpwire.com/api/ltix/');
    }
}
