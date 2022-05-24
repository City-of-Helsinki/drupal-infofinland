<?php

namespace Drupal\infofinland_brokenlink_checker\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\InvalidArgumentException;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation witch defines
 * to witch queue it applied.
 *
 * @QueueWorker(
 *   id = "broken_link_queue_processor",
 *   title = @Translation("Broken link queue Task Worker: flag outdated links"),
 *   cron = {"time" = 90}
 * )
 */
class BrokenLinkQueueProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Request client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an aalto_api PurgeUsersQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $http_client
   *   Parameter for request client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('infofinland_brokenlink_checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($response) {
    if (empty($response->field_language_link_value) || empty($response->parent_id)) {
      return;
    }

    if (!$this->checkUrlStatus($response->field_language_link_value)) {
      if ($linkNode = $this->entityTypeManager->getStorage('node')->load($response->parent_id)) {
        $linkNode->set('field_broken_link', true);
        $linkNode->save();
      }

      if ($languageLinkParagraph = $this->entityTypeManager->getStorage('paragraph')->load($response->id)) {
        $languageLinkParagraph->set('field_broken_link', true);
        $languageLinkParagraph->save();
      }
    }
  }

  /**
   * Does the HTTP fetch for a URL.
   *
   * @param string $url
   *   The url.
   *
   * @return bool
   */
  private function checkUrlStatus(string $url): bool {
    try {
      $response = $this->httpClient->get($url, ['http_errors' => false, 'allow_redirects' => false]);

      if ($response->getStatusCode() === 200) {
        return true;
      } else {
        $this->logger->info('Status code: '. $response->getStatusCode() . ' URL: ' . $url);
        return false;
      }
    } catch (RequestException $e) {
      $this->logger->warning('RequestException: ' . $e->getMessage());
    } catch (InvalidArgumentException $e) {
      $this->logger->warning('InvalidArgumentException: ' . $e->getMessage());
    }

    return false;
  }
}
