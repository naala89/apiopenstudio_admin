/**
 * JS for the admin user page.
 *
 * @package   ApiOpenStudioAdmin
 * @license   This Source Code Form is subject to the terms of the ApiOpenStudio Public License.
 *            If a copy of the MPL was not distributed with this file,
 *            You can obtain one at https://www.apiopenstudio.com/license/.
 * @author    john89 (https://gitlab.com/john89)
 * @copyright 2020-2030 Naala Pty Ltd
 * @link      https://www.apiopenstudio.com
 */

$(document).ready(function () {
  /**
   * Delete user modal.
   */
  $('.modal-user-delete-trigger').click(function () {
    const self = $(this)
    const modal = $('#modal-user-delete')
    modal.find('#user-name').html(self.attr('delete-user-username'))
    modal.find('a#delete-user').attr('href', '/user/delete/' + self.attr('delete-user-uid'))
    modal.modal('open')
  })
})
