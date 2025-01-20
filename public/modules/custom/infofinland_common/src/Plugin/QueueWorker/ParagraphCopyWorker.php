<?php

declare(strict_types=1);

namespace Drupal\infofinland_common\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\paragraphs\ParagraphInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copy paragraphs from fi-content to other languages.
 *
 * @QueueWorker(
 *   id = "paragraph_copy_worker",
 *   title = @Translation("Paragraph copy worker"),
 *   cron = {"time" = 60}
 * )
 */
final class ParagraphCopyWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.channel.infofinland_common'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!isset($data['nid']) || !isset($data['data'])) {
      return;
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $nodeStorage->resetCache([$data['nid']]);
    $entity = $nodeStorage->load($data['nid']);

    $added_paragraphs = json_decode($data['data'], TRUE);

    $entity_type_manager = \Drupal::entityTypeManager();
    $paragraph_storage = $entity_type_manager->getStorage('paragraph');
    $translation_languages = $entity->getTranslationLanguages();

    foreach ($translation_languages as $language) {
      $lang = $language->getId();
      if ($lang === 'fi' || !$entity->hasTranslation($lang)) {
        continue;
      }

      // The translation is set as a draft when paragraphs are copied.
      // The last revision must be explicitly fetched to prevent data loss.
      $node_translation = $entity->getTranslation($lang);
      if (!$node_translation->isLatestTranslationAffectedRevision()) {
        $latest_revision_id = $nodeStorage
          ->getLatestTranslationAffectedRevisionId($entity->id(), $lang);

        $node_translation = $nodeStorage->loadRevision($latest_revision_id);
        if (
          $node_translation instanceof TranslatableInterface &&
          $node_translation->hasTranslation($lang)
        ) {
          $node_translation = $node_translation->getTranslation($lang);
        }
        else {
          $error = sprintf(
            'Paragraph copier cannot find translation for some reason.
            Entity id "%s" revision "%s" langcode "%s".',
            $entity->id(),
            $latest_revision_id,
            $lang
          );
          $this->logger->error($error);
          continue;
        }
      }

      assert($node_translation instanceof EntityInterface);
      $translated_paragraphs = $node_translation->get('field_content')->getValue();

      foreach ($added_paragraphs as $added_paragraph_data) {
        $added_paragraph = $paragraph_storage->load($added_paragraph_data['target']);
        if (!$added_paragraph instanceof ParagraphInterface) {
          continue;
        }

        $duplicateParagraph = $added_paragraph->createDuplicate();
        $duplicateParagraph->set('langcode', $lang);
        $duplicateParagraph->save();

        $paragraph_reference = [
          'target_id' => $duplicateParagraph->id(),
          'target_revision_id' => $duplicateParagraph->getRevisionId(),
        ];

        // Set the paragraph in correct position.
        $paragraph_position = $added_paragraph_data['delta'];
        $this->insertItemAtPosition($translated_paragraphs, $paragraph_reference, $paragraph_position);
      }

      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = \Drupal::entityTypeManager()->getStorage($node_translation->getEntityTypeId());
      $node_translation = $storage->createRevision($node_translation, $node_translation->isDefaultRevision());

      $node_translation->set('moderation_state', 'draft');
      $node_translation->field_content = $translated_paragraphs;

      if ($node_translation instanceof RevisionLogInterface) {
        $node_translation->setRevisionLogMessage('New content added for finnish page and copied to translations');
        $node_translation->setRevisionUserId($entity->getOwnerId());
      }
      $node_translation->save();
    }
  }

  /**
   * Insert an item on any position in array while preserving the order.
   *
   * @param array $array
   *   The array we are modifying.
   * @param array $insert
   *   The content we want to add into the array.
   * @param int $position
   *   The position where the content is inserted.
   */
  private function insertItemAtPosition(array &$array, array $insert, int $position) {
    if (count($array) === 1) {
      $array[] = $insert;
      return;
    }

    $remains = array_slice($array, max(0, $position));
    $array = array_merge(
      array_slice($array, 0, $position),
      [$insert],
      $remains
    );
  }

}
