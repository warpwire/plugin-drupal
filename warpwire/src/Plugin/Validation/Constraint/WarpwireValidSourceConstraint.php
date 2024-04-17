<?php

namespace Drupal\warpwire\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value represents a valid Warpwire share link.
 *
 * @Constraint(
 *   id = "warpwire_valid_source_constraint",
 *   label = @Translation("Valid warpwire share link", context = "Validation"),
 *   type = {"link", "string", "string_long"}
 * )
 */
class WarpwireValidSourceConstraint extends Constraint
{

    /**
     * The error message if the URL does not contain a Warpwire asset shortcode.
     *
     * @var string
     */
    public $invalidNoShortcode = 'The given link does not contain a Warpwire asset shortcode.';

    /**
     * The error message if the Warpwire asset was not found when querying the server.
     * 
     * @var string
     */
    public $invalidNotFound = 'The given Warpwire asset was not found.';
}
