<?php

declare(strict_types=1);

namespace Drupal\infofinland_brokenlink_checker\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;

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
  protected $entityTypeManager;

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

    $logger_params = [
      '@id' => $response->id,
      '@parent_id' => $response->parent_id,
      '@url' => $response->field_language_link_value,
    ];

    try {
      $code = $this->checkUrlStatus($response->field_language_link_value);
    }
    catch (\Exception $e) {
      $logger_params['@message'] = $e->getMessage();
      // This happens for example following reasons,
      // cURL error 28: Operation timed out after 30001 milliseconds with 0
      // bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)
      // If this happens, the broken link status gets an empty value.
      $this->logger->warning('Checking cURL status failed: parent_id: @parent_id , id: @id ,  url: @url - @message ', $logger_params);
      return;
    }

    if ($code === 200 || $code === 301 || $code === 302 || $code === 303) {
      if ($linkNode = $this->entityTypeManager->getStorage('node')->load($response->parent_id)) {
        $linkNode->set('field_broken_link', FALSE);
        $linkNode->save();
      }

      if ($languageLinkParagraph = $this->entityTypeManager->getStorage('paragraph')->load($response->id)) {
        $languageLinkParagraph->set('field_broken_link', FALSE);
        $languageLinkParagraph->save();
      }
    }
    else {
      if ($linkNode = $this->entityTypeManager->getStorage('node')->load($response->parent_id)) {
        $linkNode->set('field_broken_link', TRUE);
        $linkNode->save();
      }

      if ($languageLinkParagraph = $this->entityTypeManager->getStorage('paragraph')->load($response->id)) {
        $languageLinkParagraph->set('field_broken_link', TRUE);
        $languageLinkParagraph->save();
      }

      $logger_params['@code'] = $code;
      $this->logger->warning('Checking URL status failed: parent_id: @parent_id , id: @id , url: @url - code: @code ', $logger_params);
    }
  }

  /**
   * Does the HTTP fetch for a URL.
   *
   * @param string $url
   *   The url.
   *
   * @return int
   *   The status code.
   */
  private function checkUrlStatus(string $url): int {
    $response = $this->httpClient->get($url, [
      'http_errors' => FALSE,
      'allow_redirects' => [
        'max'             => 3,
        'strict'          => FALSE,
        'referer'         => FALSE,
        'protocols'       => ['http', 'https'],
        'track_redirects' => FALSE,
      ],
      'verify' => FALSE,
    ]);

    return $response->getStatusCode();
  }

}
