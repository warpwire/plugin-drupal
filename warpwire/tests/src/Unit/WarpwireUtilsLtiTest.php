<?php

use Drupal\Tests\UnitTestCase;
use Drupal\warpwire\Lib\LtiConfiguration;
use Drupal\warpwire\Lib\WarpwireLtiUtils;

class WarpwireLtiUtilsTest extends UnitTestCase
{

    public function testBuildLtiParamsList()
    {
        // Arrange
        $ltiConfig = new LtiConfiguration();
        $ltiConfig->warpwireSiteUrl = 'https://example.warpwire.com';
        $ltiConfig->warpwireLtiKey = 'my-dummy-key';
        $ltiConfig->warpwireGroupName = 'my-dummy-group';
        $ltiConfig->userId = 'user1';
        $ltiConfig->userDisplayName = 'Wolfgang Mozart';
        $ltiConfig->userEmail = 'wolfgang.mozart@learning.edu';
        $ltiConfig->drupalSiteName = 'My Great Drupal Site';
        $ltiConfig->drupalVersion = '10.0.0';
        $ltiConfig->drupalLocale = 'en';
        $ltiConfig->drupalPageTitle = 'My Great Drupal Page';
        $ltiConfig->drupalPageUrl = 'https://example.com/drupal/page/1';
        $ltiConfig->resourceLinkId = 'https://url/to/launch/warpwire/iframe';
        $ltiConfig->institutionName = 'My Great Institution';

        // Act
        $result = WarpwireLtiUtils::buildLtiParamsList($ltiConfig);

        // Assert
        $this->assertArrayHasKey('oauth_nonce', $result);
        $this->assertArrayHasKey('oauth_timestamp', $result);

        // Remove these before comparing, since they are dynamic, and have been verified above
        unset($result['oauth_nonce']);
        unset($result['oauth_timestamp']);

        $expected = array(
            'oauth_version' => '1.0',
            // Removed: oauth_nonce
            // Removed: oauth_timestamp
            'oauth_consumer_key' => 'my-dummy-key',
            'oauth_callback' => 'about:blank',
            'oauth_signature_method' => 'HMAC-SHA256',
            'user_id' => 'user1',
            'lis_person_sourcedid' => 'user1',
            'roles' => '',
            'lis_person_name_given' => 'Wolfgang',
            'lis_person_name_family' => 'Mozart',
            'lis_person_name_full' => 'Wolfgang Mozart',
            'lis_person_contact_email_primary' => 'wolfgang.mozart@learning.edu',
            'ext_user_username' => 'user1',
            'ext_lms' => 'drupal',
            'tool_consumer_info_product_family_code' => 'drupal',
            'tool_consumer_info_version' => '10.0.0',
            'tool_consumer_instance_name' => 'My Great Institution',
            'lti_version' => 'LTI-1p0',
            'lti_message_type' => 'basic-lti-launch-request',
            'launch_presentation_locale' => 'en',
            'launch_presentation_document_target' => 'iframe',
            'launch_presentation_return_url' => 'https://example.com/drupal/page/1',
            'returnContext' => '',
            'context_id' => md5('https://url/to/launch/warpwire/iframe'),
            'context_label' => 'My Great Drupal Site',
            'context_title' => 'My Great Drupal Page',
            'resource_link_id' => md5('https://url/to/launch/warpwire/iframe'),
            'resource_link_title' => 'Warpwire',
            'custom_section_id' => 'https://example.com/drupal/page/1',
            'custom_module_id' => '',
        );

        $this->assertEquals($expected, $result);
    }
}
