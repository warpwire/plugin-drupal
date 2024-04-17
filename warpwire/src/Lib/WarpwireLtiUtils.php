<?php

namespace Drupal\warpwire\Lib;

/**
 * Provide LTI-related static utility functions
 */
class WarpwireLtiUtils
{

    /**
     * Build the array of LTI parameters to be used for the LTI launch, using the config object
     * 
     * @param LtiConfiguration $ltiConfig - The LTI configuration object
     * @return array - The LTI launch parameters
     */
    static function buildLtiParamsList(LtiConfiguration $ltiConfig): array
    {
        $hashedResourceLinkId = md5($ltiConfig->resourceLinkId ?? '');

        // Context ID and Label will be used to create the user group within Warpwire
        $contextId = preg_replace("/[^A-Za-z0-9]/", "-", strtolower($ltiConfig->warpwireGroupName ?? 'drupal'));
        $contextLabel = $ltiConfig->warpwireGroupName ?? 'Drupal';

        $params = array();

        // OAuth
        $params['oauth_version'] = '1.0';
        $params['oauth_nonce'] = md5(mt_rand());
        $params['oauth_timestamp'] = strtotime('+30 minutes');
        $params['oauth_consumer_key'] = $ltiConfig->warpwireLtiKey ?? '';
        // Add oauth_callback to be compliant with the 1.0A spec.
        $params['oauth_callback'] = 'about:blank';
        $params['oauth_signature_method'] = 'HMAC-SHA256';

        // User
        $params['user_id'] = $ltiConfig->userId ?? '';
        $params['lis_person_sourcedid'] = $ltiConfig->userId ?? '';
        $params['roles'] = "";
        $params['lis_person_name_given'] = $ltiConfig->userFirstName ?? '';
        $params['lis_person_name_family'] = $ltiConfig->userLastName ?? '';
        $params['lis_person_name_full'] = $ltiConfig->userDisplayName ?? '';
        $params['lis_person_contact_email_primary'] = $ltiConfig->userEmail ?? '';

        // Extensions
        $params['ext_user_username'] = $ltiConfig->userName ?? '';
        $params['ext_lms'] = 'drupal';

        // Tool consumer
        $params['tool_consumer_info_product_family_code'] = 'drupal';
        $params['tool_consumer_info_version'] = $ltiConfig->drupalVersion ?? '';
        $params['tool_consumer_instance_name'] = $ltiConfig->institutionName ?? '';

        // LTI Launch
        $params['lti_version'] = 'LTI-1p0';
        $params['lti_message_type'] = 'basic-lti-launch-request';
        $params['launch_presentation_locale'] = $ltiConfig->drupalLocale ?? '';
        $params['launch_presentation_document_target'] = 'iframe';
        $params['launch_presentation_return_url'] = $ltiConfig->drupalPageUrl ?? '';
        $params['returnContext'] = $ltiConfig->returnContext ?? '';

        // Context (group to add users to within Warpwire)
        $params['context_id'] = $contextId;
        $params['context_label'] = $contextLabel;
        $params['context_title'] = $contextLabel;

        // Content Item
        $params['resource_link_id'] = $hashedResourceLinkId;
        $params['resource_link_title'] = "Warpwire";

        // Custom
        $params['custom_section_id'] = $ltiConfig->drupalPageUrl ?? '';
        // At some point, we could support a custom_module_id value using a block-level ID
        // provided by a separate module:
        $params['custom_module_id'] = '';

        return $params;
    }

    /**
     * Compute the oauth signature for the LTI launch
     * 
     * @param string $method - The request method (POST, GET)
     * @param string $url - The request URL
     * @param array $params - The form parameters that will be posted in the request
     * @param string $secret - The LTI launch secret
     * @return string - The computed signature
     */
    static function buildOauthSignature(string $method, string $url, array $params, string $secret): string
    {

        // parse the provided url to be normalized
        $url_parts = parse_url($url);
        $normalized_url = $url_parts['scheme'] . "://" . $url_parts['host'] . $url_parts['path'];

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        $signable_params = static::buildHttpQuery($params);

        $parts = array(
            $method,
            $normalized_url,
            $signable_params
        );

        $base_string = implode('&', static::urlencodeRfc3986($parts));

        $key_parts = array(
            $secret,
            ""
        );

        $key_parts = static::urlencodeRfc3986($key_parts);
        $key = implode('&', $key_parts);

        $computed_signature = base64_encode(hash_hmac('sha256', $base_string, $key, true));
        return $computed_signature;
    }

    static function buildHttpQuery($params)
    {
        if (!$params) {
            return '';
        }

        // Urlencode both keys and values
        $keys = static::urlencodeRfc3986(array_keys($params));
        $values = static::urlencodeRfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                natsort($value);
                foreach ($value as $duplicate_value) {
                    $pairs[] = $parameter . '=' . $duplicate_value;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }

    static function urlencodeRfc3986(mixed $input)
    {
        if (is_array($input)) {
            return array_map('static::urlencodeRfc3986', $input);
        } elseif (is_scalar($input)) {
            return str_replace(
                '+',
                ' ',
                str_replace('%7E', '~', rawurlencode($input))
            );
        } else {
            return '';
        }
    }

    /**
     * Build the self-submitting HTML form to perform LTI launch
     * 
     * @param string $warpwireLtiUrl - The LTI launch URL
     * @param array $params - The LTI parameters to include in the form
     * @return string - The HTML of the form page
     */
    static function buildLtiForm(string $warpwireLtiUrl, array $params)
    {
        // build the form to submit LTI credentials
        $content = '<html><head></head><body><form id="warpwire_lti_post" method="POST" enctype="application/x-www-form-urlencoded" action="' . $warpwireLtiUrl . '">' . PHP_EOL;

        foreach ($params as $key => $value) {
            $content .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        $content .= '<div id="warpwire_display_submit"><p>Please press the Submit button to continue.</p>';
        $content .= '<p><input type="submit" value="Submit"></p></div>';
        $content .= '</form>';
        $content .= '
          <script>
            (function(){
              var warpwireDisplaySection = document.getElementById("warpwire_display_submit");
              if( (warpwireDisplaySection) && (warpwireDisplaySection != null) ) {
                warpwireDisplaySection.style.display = "none";
      
                setTimeout(function(){
                  warpwireDisplaySection.style.display = "block";
                }, 4000);
              }
      
              var warpwireLTIForm = document.getElementById("warpwire_lti_post");
      
              if( (!warpwireLTIForm) || (warpwireLTIForm == null) )
                return(false);
      
              warpwireLTIForm.submit();
            })();
          </script>';

        $content .= '</body></html>';

        return $content;
    }
}
