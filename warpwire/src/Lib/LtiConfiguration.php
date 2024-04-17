<?php

namespace Drupal\warpwire\Lib;

/**
 * Class representing an instance of LTI launch configuration
 */
class LtiConfiguration
{

    public string $warpwireSiteUrl;
    public string $warpwireLtiKey;
    public string $warpwireLtiSecret;
    public string $warpwireGroupName;

    public array $allowedLtiHosts;

    public string $userId;
    public string $userName;
    public string $userDisplayName;
    public string $userFirstName;
    public string $userLastName;
    public string $userEmail;

    public string $drupalPageUrl;
    public string $drupalVersion;
    public string $drupalLocale;

    public string $institutionName;

    public string $resourceLinkId;
    public string $returnContext;
}
