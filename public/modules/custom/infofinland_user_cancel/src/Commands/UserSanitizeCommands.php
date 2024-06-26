<?php

declare(strict_types=1);

namespace Drupal\infofinland_user_cancel\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\infofinland_user_cancel\Entity\Utility\UserEntitySanitizer;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Option;
use Drush\Attributes\Usage;
use Drush\Commands\DrushCommands;
use Drush\Utils\StringUtils;

/**
 * A Drush command file.
 */
final class UserSanitizeCommands extends DrushCommands {

  use StringTranslationTrait;

  const FIELDS = ['username', 'email', 'password'];

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\infofinland_user_cancel\Entity\Utility\UserEntitySanitizer $sanitizer
   *   UserEntitySanitizer service.
   */
  public function __construct(
    protected UserEntitySanitizer $sanitizer,
  ) {
  }

  /**
   * Sanitizes user entity fields.
   *
   * @param string $uids
   *   The user IDs.
   * @param array $options
   *   The options.
   *
   * @return int
   *   The exit code.
   */
  #[Command(name: 'user:sanitize')]
  #[Argument(name: 'uids', description: 'A comma delimited list of user ids.')]
  #[Option(name: 'fields', description: 'A comma delimited list of fields to sanitize.')]
  #[Usage(name: 'drush user:sanitize 5,6,7 --fields=username,email', description: 'Sanitize username and email fields for uids 5,6 and 7.')]
  #[Usage(name: 'drush user:sanitize 5', description: 'Sanitize username, email and password for uid 5')]
  public function sanitize(string $uids, array $options = ['fields' => self::FIELDS]) : int {
    $sanitized_users = [];

    // If fields are set, use only the specified ones.
    if ($fields = StringUtils::csvToArray($options['fields'])) {
      $fields = array_intersect($fields, self::FIELDS);
      if (!$fields) {
        $this->logger->notice(dt('There was an error in the fields list. Check the "fields" option values (username, email, password).'));
        return DrushCommands::EXIT_FAILURE;
      }
    }

    // If no fields are set, use all fields.
    if (!$fields) {
      $fields = self::FIELDS;
    }

    // Use sanitizer service to Sanitize the user entity fields.
    foreach (StringUtils::csvToArray($uids) as $uid) {
      try {
        $this->sanitizer->sanitizeUserEntity((int) $uid, $fields);
        $sanitized_users[] = $uid;
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        continue;
      }
    }

    if (!$sanitized_users) {
      $this->logger->notice(dt('No users were sanitized.'));
      return DrushCommands::EXIT_SUCCESS;
    }

    $this->logger->notice(dt('!fields fields were sanitized for UIDs !uids.', [
      '!fields' => implode(', ', $fields),
      '!uids' => implode(', ', $sanitized_users),
    ]));
    return DrushCommands::EXIT_SUCCESS;
  }

}
