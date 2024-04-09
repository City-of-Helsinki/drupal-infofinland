<?php

namespace Drupal\Tests\infofinland_user_cancel\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for anonymising node revisions when canceling users.
 */
class UserCancelNodeRevisionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of node revisions.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * User entity.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'infofinland_user_cancel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    // Create and log in user.
    $user = $this->drupalCreateUser(
      [
        'view page revisions',
        'revert page revisions',
        'edit any page content',
      ]
    );

    $this->drupalLogin($user);

    // Create initial node.
    $node = $this->drupalCreateNode();

    $nodes = [];
    $nodes[] = clone $node;

    // Create three revisions for a total of 4 including original.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      // Create revision with a random title and body and update variables.
      $node->title = $this->randomMachineName();
      $node->setNewRevision();
      $node->save();
      $nodes[] = clone $node;
    }

    $this->user = $user;
    $this->nodes = $nodes;
  }

  /**
   * Check that revisions exist.
   */
  public function testRevisionsExist() {
    $node = reset($this->nodes);

    $connection = Database::getConnection();
    $revision_count = $connection->select('node_revision')
      ->condition('revision_uid', $this->user->id())
      ->condition('nid', $node->id())
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(4, (int) $revision_count, 'Amount of revisions does not match.');
  }

  /**
   * Check that revision anonymisation function works.
   */
  public function testRevisionAnonymisation() {
    $node = reset($this->nodes);

    // Run function for test user.
    _infofinland_user_cancel_reassign_nodes_to_anonymous($this->user);

    // Test that revisions for this user were anonymized correctly.
    $connection = Database::getConnection();
    $revision_count = $connection->select('node_revision')
      ->condition('revision_uid', $this->user->id())
      ->condition('nid', $node->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, (int) $revision_count, 'Found revisions after running anonymisation function.');

    // Test that the revisions were correctly assigned to uid 0.
    $anon_revision_count = $connection->select('node_revision')
      ->condition('revision_uid', 0)
      ->condition('nid', $node->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(4, (int) $anon_revision_count, 'Amount of anonymized revisions does not match');
  }

  /**
   * Test that infofinland_user_cancel hook is invoked before node module.
   */
  public function testUserCancelHookOrder() {
    $moduleHandler = \Drupal::moduleHandler();
    $module_found = FALSE;
    $moduleHandler->invokeAllWith('user_cancel', function (callable $hook, string $module) use (&$module_found) {
      if ($module === 'infofinland_user_cancel') {
        $module_found = TRUE;
      }
      if ($module === 'node') {
        $this->assertTrue($module_found, 'Node user_cancel hook found before infofinland_user_cancel hook.');
      }
    });
  }

}
