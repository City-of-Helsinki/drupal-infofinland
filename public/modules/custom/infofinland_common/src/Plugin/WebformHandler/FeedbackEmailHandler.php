<?php

declare(strict_types=1);

namespace Drupal\infofinland_common\Plugin\WebformHandler;

use Drupal\node\NodeInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Set feedback email for webform submissions.
 *
 * @WebformHandler(
 *   id = "infofinland_feedback_email",
 *   label = @Translation("Feedback email"),
 *   category = @Translation("Infofinland"),
 *   description = @Translation("Adds feedback email field to submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class FeedbackEmailHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() : array {
    return [
      'default_email' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) : void {
    $webform_submission->setElementData('feedback_email', $this->getDefaultFeedbackEmail());

    // Override default email with node field.
    if ($id = $webform_submission->getElementData('uuid')) {
      $node = $this->entityTypeManager
        ->getStorage('node')
        ->loadByProperties([
          'uuid' => $id,
        ]);

      $node = reset($node);
      if (
        $node instanceof NodeInterface &&
        $node->hasField('field_feedback_email') &&
        !$node->get('field_feedback_email')->isEmpty()
      ) {
        $webform_submission->setElementData('feedback_email', $node->get('field_feedback_email')->getString());
      }
    }
  }

  /**
   * Gets default feedback email.
   *
   * @return string|null
   */
  private function getDefaultFeedbackEmail() : ?string {
    return $this->configuration['default_email'] ?? NULL;
  }

}
