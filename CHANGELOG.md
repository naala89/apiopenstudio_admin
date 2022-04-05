v1.0.0-beta-RC1
===============

- Imported admin code from api_open_studio.
- Converted gulpfile from v3 to v4.
- renamed the namespaces.
- Consolidated /src and /includes.
- Reduced the number of packages in composer.
- Fixed and tidied the sass, removing specific class from the markup.
- Fixed issue where logout was not happening at the API end.

v1.0.0-alpha
============

- Resolved all issues so that admin was talking to ApiOpenStudio

v1.0.0-alpha1
=============

- Skipped

v1.0.0-alpha2
=============

- Updated the create/edit resource page to work with the changes in
  ApiOpenStudio (see issue #54 in ApiOpenStudio GitLab repo).
- License updates.

v1.0.0-alpha3
=============

- Gulpfile fixes.
- README.md update.
- Updated authentication to use the new JWT aut.
- Updated handling of responses for:
  - vars
  - users
  - roles
  - resources

v1.0.0-beta
===========

- Added OpenApi UI and editor.
- Updated all API calls the handle possible new JSON response objects
  (see https://gitlab.com/apiopenstudio/apiopenstudio_admin/-/issues/23):
  - New JSON error response object.
  - Responses can now be raw JSON response or a JSON response object.
