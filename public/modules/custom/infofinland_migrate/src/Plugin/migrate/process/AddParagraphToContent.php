<?php

namespace Drupal\infofinland_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "add_paragraphs"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_content:
 *   plugin: add_paragraphs
 *   source: text
 * @endcode
 *
 */
class AddParagraphToContent extends ProcessPluginBase {

  /**
   * @param Row $row
   * @return array
   */
  public function getData(Row $row): array
  {
    $paragraphs = [];
    $drupalDb = Database::getConnection('default', 'default');
    if (!is_null($row->getSourceProperty('Dokumentin ID')) && $row->getSourceProperty('Dokumentin ID') !== '') {
      $results = $drupalDb->select('paragraph__field_migration_id', 'pfm')
        ->fields('pfm', ['field_migration_id_value', 'entity_id', 'revision_id'])
        ->condition('pfm.field_migration_id_value', $row->getSourceProperty('Dokumentin ID'), '=')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        $firstParagraph = Paragraph::load($results[0]->entity_id);
        // This paragraph is actually in description so we dont want it in content
        if (isset($firstParagraph->field_text->value) &&
          !str_contains($firstParagraph->field_text->value, '<ul>') &&
          !str_contains($firstParagraph->field_text->value, '<a')) {
          array_shift($results);
        }
        foreach ($results as $result) {
          $paragraphs[] = [
            'target_id' => $result->entity_id,
            'target_revision_id' => $result->revision_id,
          ];
        }
      }
    }

    return $paragraphs;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = [];
    $paragraphs = $this->getData($row);
    foreach($paragraphs as $target) {
      $return[] = ['target_id' => $target];
    }

    return $paragraphs;
  }


}
