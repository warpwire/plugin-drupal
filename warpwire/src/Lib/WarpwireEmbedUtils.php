<?php

namespace Drupal\warpwire\Lib;

/**
 * Provide embed-related static utility functions
 */
class WarpwireEmbedUtils
{

    static function constructEmbed(string $targetUrl, bool $useLtiLaunch)
    {

        $wwConfig = \Drupal::config('warpwire.settings');

        // Parse the config WW url
        $parsedConfigUrl = new WarpwireAssetUrl($wwConfig->get('warpwire.lti_url'));

        // Verify that the config URL is valid
        if (!$parsedConfigUrl->isValidSiteUrl()) {
            return [
                '#type' => 'html_tag',
                '#tag' => 'div',
                "#value" => "Warpwire site URL is not properly configured."
            ];
        }

        // Parse the target (media asset) URL
        $parsedTargetUrl = new WarpwireAssetUrl($targetUrl);

        // Check to see if the target URL is a valid Warpwire URL (with a shortcode)
        if (!$parsedTargetUrl->isValidAssetUrl()) {
            return  [
                '#type' => 'html_tag',
                '#tag' => 'div',
                "#value" => "Unable to load Warpwire media due to invalid URL."
            ];
        }

        // Determine if the media source URL is from the configured site for this Drupal instance,
        // or from another Warpwire site/instance.
        $isFromConfiguredSite = $parsedTargetUrl->host === $parsedConfigUrl->host;

        // Build the effective embed configuration based on defaults and input query params
        $parameters = $parsedTargetUrl->query_params;

        if (!isset($parameters['share']) && !$wwConfig->get('warpwire.default_video_display_share')) {
            $parameters['share'] = 'false';
        }

        if (!isset($parameters['title']) && !$wwConfig->get('warpwire.default_video_display_title')) {
            $parameters['title'] = 'false';
        }

        if (!isset($parameters['autoplay']) && $wwConfig->get('warpwire.default_video_display_autoplay')) {
            $parameters['autoplay'] = 'true';
        }

        if (!isset($parameters['cc_load_policy']) && $wwConfig->get('warpwire.default_video_display_cc')) {
            $parameters['cc_load_policy'] = 1;
        }

        // Determine the URL to use for the iframe
        // If the user is authenticated and the media source URL matches the configured site,
        // use LTI launch to embed the media. Otherwise use the media source URL directly.
        $url = '';
        if ($useLtiLaunch && $isFromConfiguredSite) {

            // Generate the asset URL with effective query parameters
            $urlParam = $parsedTargetUrl->asset_url . '?' . http_build_query($parameters);

            // Get the name of the current Drupal page
            $request = \Drupal::request();
            $routeMatch = \Drupal::routeMatch();
            $drupalPageTitle = \Drupal::service('title_resolver')->getTitle($request, $routeMatch->getRouteObject());

            // Pass launch URL and additional metadata to the iframe
            $launchInput = array(
                'q' => 'warpwire/external_content',
                'url' => urlencode($urlParam),
                'drupal_page_title' => urlencode($drupalPageTitle ?? ''),
                'drupal_page_path' => urlencode(\Drupal::service('path.current')->getPath() ?? ''),
            );

            // This is the path to the WarpwireLaunchController, which loads inside the iframe
            $url = '/warpwire/launch?' . http_build_query($launchInput);
        } else {
            // If we're not using LTI launch, simply set the iframe to use the media source URL
            $url = $targetUrl;
        }

        // Default width and height values for iframe
        $iframeWidth = $wwConfig->get('warpwire.default_video_width') ?? 480;
        $iframeHeight = $wwConfig->get('warpwire.default_video_height') ?? 360;

        // Use width/height from parameters if provided
        if (!empty($parameters['width'])) {
            $iframeWidth = $parameters['width'];
        }
        if (!empty($parameters['height'])) {
            $iframeHeight = $parameters['height'];
        }

        return  [
            '#type' => 'html_tag',
            '#tag' => 'iframe',
            '#attributes' => [
                'src' => $url,
                'frameborder' => 0,
                'allow' => 'autoplay *; encrypted-media *;fullscreen *;',
                'allowfullscreen' => TRUE,
                'webkitallowfullscreen' => TRUE,
                'mozallowfullscreen' => TRUE,
                'allowtransparency' => TRUE,
                'height' => $iframeHeight . 'px',
                'width' => $iframeWidth . 'px',
                'class' => ['media-oembed-content'],
            ],
        ];
    }
}
