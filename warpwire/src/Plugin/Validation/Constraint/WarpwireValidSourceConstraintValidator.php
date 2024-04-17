<?php

namespace Drupal\warpwire\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\warpwire\Lib\WarpwireAssetUrl;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\warpwire\WarpwireClient;

/**
 * Validates the WarpwireValidSourceConstraint constraint.
 */
class WarpwireValidSourceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface
{
    /**
     * The Warpwire client.
     * 
     * @var \Drupal\warpwire\WarpwireClient
     */
    protected WarpwireClient | NULL $warpwireClient;

    public function __construct(WarpwireClient $warpwire_client = NULL)
    {
        $this->warpwireClient = $warpwire_client;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('warpwire.warpwire_client'),
        );
    }

    public function validate($value, Constraint $constraint): void
    {
        foreach ($value as $item) {

            // Check for valid Warpwire asset URL pattern
            $asset = new WarpwireAssetUrl($item->value);
            if (!$asset->isValidAssetUrl()) {
                $this->context->addViolation($constraint->invalidNoShortcode, ['%value' => $item->value]);
                return;
            }

            // If the client is available, attempt to request the resource oembed URL for validity
            if ($this->warpwireClient) {
                $response = $this->warpwireClient->fetchWarpwireMetadata($asset->oembed_url);
                if ($response === NULL) {
                    $this->context->addViolation($constraint->invalidNotFound, ['%value' => $item->value]);
                    return;
                }
            }
        }
    }
}
