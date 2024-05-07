<?php

declare(strict_types=1);

namespace Drupal\Tests\infofinland_user_cancel\Functional;

use Drupal\Tests\helfi_api_base\Functional\BrowserTestBase;
use Drupal\user\Entity\User;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests user sanitation form and drush command.
 *
 * @group infofinland_user_cancel
 * @covers \Drupal\infofinland_user_cancel\Commands\UserSanitizeCommands
 */
class UserSanitizeCommandsTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'infofinland_user_cancel',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User account for testing the sanitation form.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testUser;

  /**
   * Default values for the test user.
   *
   * @var array|string[]
   */
  private array $defaultValues = [
    'username' => 'Test',
    'password' => 'test',
    'email' => 'lTqgB@drupal.hel.ninja',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Create a test user with default values.
    $this->testUser = $this->createUser(
      ['access content'],
      $this->defaultValues['username'],
      FALSE,
      [
        'pass' => $this->defaultValues['password'],
        'mail' => $this->defaultValues['email'],
      ],
    );
    $this->testUser->block()->save();
  }

  /**
   * Tests helfi:yser-sanitize command with field options.
   */
  public function testUserSanitizeCommandWithFields() {
    $this->drush('user:sanitize', [$this->testUser->id()], ['fields' => 'username']);

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $storage->resetCache([$this->testUser->id()]);
    $entity = $storage->load($this->testUser->id());

    $this->assertEquals($entity->getAccountName() === $this->defaultValues['username'], FALSE);
    $this->assertEquals($entity->getEmail() === $this->defaultValues['email'], TRUE);
    $password_service = $this->container->get('password');
    $this->assertEquals($password_service->check($this->defaultValues['password'], $entity->getPassword()), TRUE);
  }

  /**
   * Tests helfi:yser-sanitize command without field options.
   */
  public function testUserSanitizeCommandWithOutFields() {
    $this->drush('user:sanitize', [$this->testUser->id()]);

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $storage->resetCache([$this->testUser->id()]);
    $entity = $storage->load($this->testUser->id());

    $this->assertEquals($entity->getAccountName() === $this->defaultValues['username'], FALSE);
    $this->assertEquals($entity->getEmail() === $this->defaultValues['email'], FALSE);
    $password_service = $this->container->get('password');
    $this->assertEquals($password_service->check($this->defaultValues['password'], $entity->getPassword()), FALSE);
  }

}
