<?php

namespace Drupal\warpwire\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media\Entity\MediaType;
use Drupal\warpwire\Lib\WarpwireEmbedUtils;
use Drupal\warpwire\Plugin\media\Source\WarpwireSource;

/**
 * Format instances of Warpwire media source for display in HTML pages.
 *
 * @FieldFormatter(
 *   id = "warpwire_warpwire_embed_formatter",
 *   label = @Translation("Warpwire formatter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class WarpwireEmbedFormatter extends FormatterBase
{

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        // If user is authenticated and has LTI launch permissions, use LTI launch
        // Otherwise, simply embed the media using the media source URL.
        $useLtiLaunch =
            \Drupal::currentUser()->isAuthenticated() &&
            \Drupal::currentUser()->hasPermission('warpwire_lti_launch');

        $element = [];
        foreach ($items as $delta => $item) {
            // For each item, construct the embed iframe
            $element[$delta] = WarpwireEmbedUtils::constructEmbed($item->value, $useLtiLaunch);
        }

        return $element;
    }

    public static function isApplicable(FieldDefinitionInterface $field_definition)
    {
        if ($field_definition->getTargetEntityTypeId() !== 'media') {
            return FALSE;
        }

        if (parent::isApplicable($field_definition)) {
            $media_type = $field_definition->getTargetBundle();

            if ($media_type) {
                $media_type = MediaType::load($media_type);
                return $media_type && $media_type->getSource() instanceof WarpwireSource;
            }
        }
        return FALSE;
    }
}
