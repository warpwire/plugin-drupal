<?php

namespace Drupal\warpwire\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\warpwire\Lib\LtiConfiguration;
use Drupal\warpwire\Lib\WarpwireAssetUrl;
use Drupal\warpwire\Lib\WarpwireUtils;
use Drupal\warpwire\Lib\WarpwireLtiUtils;

/**
 * Controller that builds the Warpwire LTI launch page (inside the iframe)
 */
class WarpwireLaunchController extends ControllerBase
{

  /**
   * Build the page that loads inside an iframe (at /warpwire/launch?url...) for LTI launch
   * @return Response - The response object
   */
  public function launch(): Response
  {
    $response = new Response();
    $response->headers->set('Content-Type', 'text/html');
    $this->buildPageContent($response);
    return $response;
  }

  /**
   * Build the page content for the Warpwire LTI launch
   * 
   * Perform the appropriate checks on the warpwire configuration and the launch URL.
   * Update the response object with the appropriate content, and return.
   * 
   * If the user is authenticated, and the launch URL and Warpwire config are valid,
   * build the LTI launch form. Otherwise, build an error message.
   * 
   * @param mixed $response - The response object to update
   * @return void 
   */
  public function buildPageContent($response): void
  {
    $wwConfig = $this->config('warpwire.settings');

    // Parse the config WW url
    $parsedConfigUrl = new WarpwireAssetUrl($wwConfig->get('warpwire.lti_url'));

    // Verify that the config URL is valid
    if (!$parsedConfigUrl->isValidSiteUrl()) {
      $response->setContent(
        WarpwireUtils::buildErrorMessageHtml('Warpwire site URL is not properly configured.')
      );
      return;
    }

    // Parse the target WW url from the query string
    $parsedTargetUrl = new WarpwireAssetUrl(urldecode($_GET['url'] ?? ''));

    // Check to see if the target URL is a valid Warpwire URL (with a shortcode)
    if (!$parsedTargetUrl->isValidAssetUrl()) {
      $response->setContent(
        WarpwireUtils::buildErrorMessageHtml('Unable to load Warpwire media due to invalid URL.')
      );
      return;
    }

    // Check to see that the target URL host matches the configured Warpwire host
    if ($parsedConfigUrl->host !== $parsedTargetUrl->host) {
      $response->setContent(
        WarpwireUtils::buildErrorMessageHtml('URL host does not match configured Warpwire host.')
      );
      return;
    }

    // Only allow LTI launch process to be used if user is authenticated
    if (!\Drupal::currentUser()->isAuthenticated()) {
      $response->setContent(
        WarpwireUtils::buildErrorMessageHtml('User must be authenticated to use Warpwire LTI launch.')
      );
      return;
    }

    // Only allow LTI launch if user has "LTI launch Warpwire content" permission
    if (!\Drupal::currentUser()->hasPermission('warpwire_lti_launch')) {
      $response->setContent(
        WarpwireUtils::buildErrorMessageHtml('User must have \"LTI launch Warpwire content\" permission to use Warpwire LTI launch.')
      );
      return;
    }

    // If all checks have passed, generate the iframe content
    $response->setContent($this->buildWarpwireIFrameContent($parsedConfigUrl));
  }

  /**
   * Build the self-posting form to load inside the iframe for LTI launch
   * 
   * @param WarpwireAssetUrl $parsedConfigUrl - The parsed config URL object
   * @return string
   */
  function buildWarpwireIFrameContent(WarpwireAssetUrl $parsedConfigUrl)
  {
    // Information from settings
    $wwConfig = $this->config('warpwire.settings');
    $wwLtiSecret = $wwConfig->get('warpwire.lti_secret');
    $wwLtiUrl = $parsedConfigUrl->lti_url;

    // Information from query params
    $targetUrl = urldecode($_GET['url'] ?? '');
    $drupalPagePath = urldecode($_GET['drupal_page_path'] ?? '');

    // Drupal current user info
    $user = \Drupal::currentUser();

    // Drupal site config values
    $drupalSiteName = \Drupal::config('system.site')->get('name');
    $institutionName = $wwConfig->get('warpwire.lti_institution_name') ?? 'Drupal site: ' . $drupalSiteName;

    // Configure input to LTI params mapper
    $ltiConfig = new LtiConfiguration();
    $ltiConfig->warpwireSiteUrl = $parsedConfigUrl->site_url;
    $ltiConfig->warpwireLtiKey = $wwConfig->get('warpwire.lti_key');
    $ltiConfig->warpwireGroupName = $wwConfig->get('warpwire.group_name');

    $ltiConfig->userId = $user->id();
    $ltiConfig->userName = $user->getAccountName();
    $ltiConfig->userDisplayName = $user->getAccountName();
    $ltiConfig->userFirstName = $user->getAccountName();
    $ltiConfig->userLastName = 'Drupal';

    $ltiConfig->userEmail = $user->getEmail() ?? '';

    $ltiConfig->drupalVersion = strval(\Drupal::VERSION);
    $ltiConfig->drupalLocale = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $ltiConfig->institutionName = $institutionName;

    $ltiConfig->resourceLinkId = $targetUrl;
    $ltiConfig->drupalPageUrl = \Drupal::request()->getSchemeAndHttpHost() . $drupalPagePath;
    $ltiConfig->returnContext = $targetUrl;

    $params = WarpwireLtiUtils::buildLtiParamsList($ltiConfig);

    // build the OAuth signature
    $params['oauth_signature'] = WarpwireLtiUtils::buildOauthSignature('POST', $wwLtiUrl, $params, $wwLtiSecret);

    // Build and return the self-posting LTI launch form
    return WarpwireLtiUtils::buildLtiForm($wwLtiUrl, $params);
  }
}
