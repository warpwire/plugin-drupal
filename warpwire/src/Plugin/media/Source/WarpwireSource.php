<?php

namespace Drupal\warpwire\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\warpwire\Lib\WarpwireAssetUrl;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\warpwire\WarpwireClient;

/**
 * External Warpwire asset media source.
 *
 * @MediaSource(
 *   id = "warpwire_source",
 *   label = @Translation("Warpwire Media"),
 *   description = @Translation("Use Warpwire media assets."),
 *   allowed_field_types = {"string"},
 *   thumbnail_alt_metadata_attribute = "alt",
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\warpwire\Form\WarpwireMediaLibraryAddForm"
 *   }
 * )
 */
class WarpwireSource extends MediaSourceBase
{

    /**
     * The file system.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * The logger channel for media.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The Warpwire Client service
     *
     * @var \Drupal\warpwire\WarpwireClient
     */
    protected $warpwireClient;

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTypeManagerInterface $entity_type_manager,
        EntityFieldManagerInterface $entity_field_manager,
        FieldTypePluginManagerInterface $field_type_manager,
        ConfigFactoryInterface $config_factory,
        ClientInterface $http_client,
        FileSystemInterface $file_system,
        LoggerInterface $logger,
        WarpwireClient $warpwire_client
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);

        $this->httpClient = $http_client;
        $this->fileSystem = $file_system;
        $this->logger = $logger;
        $this->warpwireClient = $warpwire_client;
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager'),
            $container->get('entity_field.manager'),
            $container->get('plugin.manager.field.field_type'),
            $container->get('config.factory'),
            $container->get('http_client'),
            $container->get('file_system'),
            $container->get('logger.factory')->get('media'),
            $container->get('warpwire.warpwire_client')
        );
    }

    public function defaultConfiguration()
    {
        return [
            'thumbnail' => $this->t('Link to the Warpwire thumbnail image'),
            'thumbnail_local' => $this->t('Copies Warpwire thumbnail image to the local filesystem and returns the URI.'),
            'thumbnails_directory' => 'public://warpwire_thumbnails',
            'generate_thumbnails' => TRUE,
        ] + parent::defaultConfiguration();
    }

    /**
     * This is part of the Drupal media source interface.
     * Return a list of metadata attributes that can be derived from the source value (the Warpwire asset URL)
     * This is only run when adding new media to the library, or editing existing media.
     */
    public function getMetadataAttributes()
    {
        return [
            'warpwire_full_url' => $this->t('Warpwire Full URL'),
            'warpwire_shortcode' => $this->t('Warpwire Shortcode'),
            'width' => $this->t('Width'),
            'height' => $this->t('Height'),
            'start_at' => $this->t('Start At'),
            'stop_at' => $this->t('Stop At'),
            'autoplay' => $this->t('Autoplay'),
            'hide_controls' => $this->t('Hide Controls'),
            'audio_only' => $this->t('Audio Only'),
            'prevent_skipping_ahead' => $this->t('Prevent Skipping Ahead'),
            'interactive_transcript' => $this->t('Interactive Transcript')
        ];
    }

    /**
     * This is part of the Drupal media source interface.
     * Given a source value (the Warpwire asset URL), and the name of a metadata attribute,
     * return the value of the attribute.
     * This is only run when adding new media to the library, or editing existing media.
     */
    public function getMetadata(MediaInterface $media, $attribute_name)
    {
        $sourceValue = $this->getSourceFieldValue($media);

        // Parse and validate the share link (using the configured host for validation)
        $sourceMetadata = new WarpwireAssetUrl($sourceValue);
        if (!$sourceMetadata->isValidSiteUrl() || !$sourceMetadata->isValidAssetUrl()) {
            return NULL;
        }

        // These are Drupal media element properties
        switch ($attribute_name) {
            case 'default_name':
                $metadata = $this->warpwireClient->fetchWarpwireMetadata($sourceMetadata->oembed_url);
                return $metadata['title'];
            case 'image':
                return $this->getMetadata($media, 'thumbnail');
            case 'thumbnail_uri':
                return $this->getMetadata($media, 'thumbnail_local');
        }

        // These are custom Warpwire properties
        switch ($attribute_name) {
            case 'warpwire_full_url':
                return $sourceMetadata->url;
            case 'warpwire_shortcode':
                return $sourceMetadata->shortcode;
            case 'width':
            case 'height':
            case 'start_at':
            case 'stop_at':
            case 'autoplay':
            case 'hide_controls':
            case 'audio_only':
            case 'prevent_skipping_ahead':
            case 'interactive_transcript':
                return $sourceMetadata[$attribute_name];
            case 'thumbnail':
                $metadata = $this->warpwireClient->fetchWarpwireMetadata($sourceMetadata->oembed_url);
                return $metadata['thumbnail_url'];
            case 'thumbnail_local':
                return $this->getLocalThumbnailUri($this->getMetadata($media, 'thumbnail'), $sourceMetadata->shortcode);
        }

        // If no case matches, return the parent's value
        return parent::getMetadata($media, $attribute_name);
    }

    /**
     * Helper method to save a Warpwire thumbnail to the local filesystem and return its URI.
     */
    protected function getLocalThumbnailUri($remoteThumbnailUri, $shortcode)
    {
        if (!$remoteThumbnailUri) {
            return NULL;
        }

        // Compute the local thumbnail URI, regardless of whether or not it exists.
        $directory = $this->configuration['thumbnails_directory'];
        $localThumbnailUri = "$directory/warpwire_thumbnail_" . $shortcode . ".jpg";

        // If the local thumbnail already exists, return its URI.
        if (file_exists($localThumbnailUri)) {
            return $localThumbnailUri;
        }

        // The local thumbnail doesn't exist yet, so try to download it.
        // First, ensure that the destination directory is writable.
        if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
            $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
                '@dir' => $directory,
            ]);
            return NULL;
        }

        try {
            $response = $this->httpClient->get($remoteThumbnailUri);
            if ($response->getStatusCode() === 200) {
                $this->fileSystem->saveData((string) $response->getBody(), $localThumbnailUri, FileSystemInterface::EXISTS_REPLACE);
                return $localThumbnailUri;
            } else {
                $this->logger->warning('Could not download remote thumbnail from {url}.', [
                    'url' => $remoteThumbnailUri,
                ]);
            }
        } catch (RequestException $e) {
            $this->logger->warning($e->getMessage());
        } catch (FileException $e) {
            $this->logger->warning('Could not download remote thumbnail from {url}.', [
                'url' => $remoteThumbnailUri,
            ]);
        }

        // If we couldn't download the remote thumbnail, use the default image.   
        $defaultImagePath = \Drupal::service('extension.list.module')->getPath('warpwire') . '/images/warpwire-default.png';
        $this->fileSystem->saveData(file_get_contents($defaultImagePath), $localThumbnailUri, FileSystemInterface::EXISTS_REPLACE);
        return $localThumbnailUri;
    }
}
