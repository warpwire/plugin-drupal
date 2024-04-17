<?php

namespace Drupal\warpwire;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\File\Exception\FileException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * Client service used to make requests to the Warpwire API
 */
class WarpwireClient
{
    /**
     * The config factory.
     *  
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * The logger service.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected $logger;

    public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory)
    {
        $this->configFactory = $config_factory;
        $this->httpClient = $http_client;
        $this->logger = $logger_factory->get('media');
    }

    /**
     * Fetch Warpwire metadata for an asset
     * 
     * @param string $assetUrl 
     * @return mixed 
     * @throws GuzzleException 
     */
    public function fetchWarpwireMetadata(string $assetUrl)
    {
        try {
            $response = $this->httpClient->get($assetUrl);
            if ($response->getStatusCode() === 200) {
                $body = $response->getBody();
                $data = json_decode($body, true);
                return $data;
            } else {
                return NULL;
            }
        } catch (RequestException $e) {
            $this->logger->warning($e->getMessage());
        } catch (FileException $e) {
            $this->logger->warning('Could not access Warpwire metadata for {url}.', [
                'url' => $assetUrl,
            ]);
        }
        return NULL;
    }
}
