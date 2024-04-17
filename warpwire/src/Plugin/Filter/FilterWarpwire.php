<?php

namespace Drupal\warpwire\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\warpwire\Lib\WarpwireEmbedUtils;

/**
 * Provide a filter to handle legacy Warpwire embeds using the pattern [warpwire:url]
 *
 * @Filter(
 *   id = "warpwire_filter",
 *   title = @Translation("Warpwire Filter (legacy support)"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "replace_empty" = FALSE
 *   }
 * )
 */
class FilterWarpwire extends FilterBase
{

  public function process($text, $langcode)
  {
    // Replace each occurrence of [warpwire:url] with the appropriate iframe embed
    $text = preg_replace_callback('/\[warpwire:([^\]]+)\]/is', function ($matches) {
      $targetUrl = htmlspecialchars_decode($matches[1]);
      // If user is authenticated and has LTI launch permissions, use LTI launch
      // Otherwise, simply embed the media using the media source URL.
      $useLtiLaunch =
        \Drupal::currentUser()->isAuthenticated() &&
        \Drupal::currentUser()->hasPermission('warpwire_lti_launch');
      $iframeArray = WarpwireEmbedUtils::constructEmbed($targetUrl, $useLtiLaunch);
      // For the preg_replace, we need to output raw HTML instead of a render array
      return \Drupal::service('renderer')->render($iframeArray);
    }, $text);

    return new FilterProcessResult($text);
  }
}
