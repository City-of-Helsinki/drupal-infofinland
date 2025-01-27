<?php

namespace Drupal\Tests\dtt\ExistingSite;

use Drupal\Tests\helfi_api_base\Functional\ExistingSiteTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;

class FeedbackFormTest extends ExistingSiteTestBase {

  /**
   * Tests feedback form feedback_email field.
   */
  public function testFeedbackEmail() {
    $webform = Webform::load('infofinland_contact');
    $handler = $webform->getHandler('feedback_email');

    // Feedback email handler must be enabled.
    $this->assertTrue($handler->isEnabled());
  }

  /**
   * Submits webform.
   */
  private function submit(WebformInterface $webform, array $values) : WebformSubmissionInterface {
    $webform_submission = WebformSubmissionForm::submitFormValues([
      'webform_id' => $webform->id(),
      'data' => $values,
    ]);

    $this->assertInstanceOf(WebformSubmissionInterface::class, $webform_submission, "Submitting webform failed");
    $this->markEntityForCleanup($webform_submission);

    return $webform_submission;
  }

}
