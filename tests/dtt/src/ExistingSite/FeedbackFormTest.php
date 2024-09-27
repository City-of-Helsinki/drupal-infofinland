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
    $webform = Webform::load('contact');
    $handler = $webform->getHandler('feedback_email');

    // Feedback email handler must be enabled.
    $this->assertTrue($handler->isEnabled());

    $test_email = 'something@example.com';
    ['default_email' => $default_email] = $handler->getSettings();

    $user = $this->createUser(['administer webform submission']);
    $this->drupalLogin($user);

    $submission = $this->submit($webform, [
      'sender_email' => 'my-email@example.com',
      'page' => 'http://localhost:3000',
      'name' => 'My name',
      'subject' => 'Subject',
      'message' => 'Message',
      'feedback_email' => 'evil@example.com',
    ]);

    // Default email address is used.
    $this->assertToAddress($default_email, $submission);

    $node = $this->createNode([
      'type' => 'landing_page',
      'field_feedback_email' => $test_email,
    ]);

    $submission = $this->submit($webform, [
      'sender_email' => 'my-email@example.com',
      'page' => 'http://localhost:3000',
      'name' => 'My name',
      'subject' => 'Subject',
      'message' => 'Message',
      'uuid' => $node->uuid(),
      'feedback_email' => 'evil@example.com',
    ]);

    // Nodes can override email address.
    $this->assertToAddress($test_email, $submission);
  }

  /**
   * Assserts that webform submission sent email to expected address.
   */
  private function assertToAddress(string $expected, WebformSubmissionInterface $submission) {
    $this->drupalGet("admin/structure/webform/manage/contact/submission/" . $submission->id() . '/resend');
    $this->assertSession()->elementAttributeContains(
      'xpath',
      '//input[@name="message[to_mail]"]',
      'value',
      $expected,
    );
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
