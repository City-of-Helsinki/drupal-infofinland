# User sanitation

Administrators have the capability to sanitize users from both list operations in the `People` page and from the user edit page, provided the user has been blocked.

## Usage

To utilize this feature, assign the `sanitize user accounts` permission to the administrator role or its equivalent. There is also a drush command to run the same feature.

### Drush command
Sanitize username and email fields for uids 5,6 and 7.
`drush user:sanitize 5,6,7 --fields=username,email`

Sanitize username, email and password for uid 5
`drush user:sanitize 5`
