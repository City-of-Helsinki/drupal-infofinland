<?php

declare(strict_types=1);

namespace Drupal\infofinland_common\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class that is used to combine content paragraphs.
 */
class ContentCombiner {

  /**
   * Combine paragraphs content.
   *
   * Loop through field content and combines text paragraphs into a one.
   * After it removes the combined ones from the node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Node to process.
   * @param bool $includeRevision
   *   To include revisions or not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function combineContentParagraphs(EntityInterface $node, $includeRevision = TRUE) {
    $paragraphs = $node->get('field_content')->referencedEntities();
    $combined = '';
    $combinedKeys = [];
    $newContent = [];
    foreach ($paragraphs as $key => $paragraph) {
      // We only want to combine text paragraphs.
      if ($paragraph->getType() !== 'text') {
        continue;
      }
      // If the next paragraph is not text we have nothing to combine it with.
      if (((isset($paragraphs[$key + 1]) && $paragraphs[$key + 1]->getType() != 'text')
        || !isset($paragraphs[$key + 1])) && $combined == '') {
        continue;
      }

      // Delta is used to position the paragraph correctly.
      if (!isset($delta)) {
        $delta = $key;
      }
      $fieldText = $paragraph->field_text->value;

      // Sometimes we are missing tags so we need to add them.
      if (!str_starts_with($fieldText, '<p') && !str_starts_with($fieldText, '<h3')
        && !str_starts_with($fieldText, '<h4') && !str_starts_with($fieldText, '<a')
        && !str_starts_with($fieldText, '<ul') && !str_starts_with($fieldText, '<h6')) {
        $fieldText = '<p>' . $fieldText . '</p>';
      }
      $combined = $combined . $fieldText;
      $combinedKeys[] = $key;

      // If next paragraph isn't text, but we combined things,
      // we need to create a new paragraph.
      if (((isset($paragraphs[$key + 1]) && $paragraphs[$key + 1]->getType() != 'text')
          || !isset($paragraphs[$key + 1]))
        && $combined != '' && count($combinedKeys) > 1) {
        $newParagraph = Paragraph::create([
          'type' => 'text',
          'langcode' => $node->langcode->value,
          'delta' => $delta,
          'field_text' => [
            "value"  => ltrim($combined),
            "format" => "full_html",
          ],
        ]);
        $newContent[$delta] = $newParagraph;
        unset($delta);
        $combined = '';
      }
    }

    // We didn't find anything to combine.
    if (empty($combinedKeys)) {
      return;
    }

    foreach ($paragraphs as $key => $paragraph) {
      if (!in_array($key, $combinedKeys)) {
        $newContent[$key] = $paragraph;
      }
    }

    ksort($newContent);
    $node->field_content = $newContent;

    if ($includeRevision) {
      $node->setNewRevision(TRUE);
      $node->revision_log = 'Automatically combined content for ' . $node->id();
      $node->setRevisionCreationTime(time());
    }
    $node->save();
  }

}
