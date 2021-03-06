/**
 * JS for the admin role page.
 *
 * @package   ApiOpenStudioAdmin
 * @license   This Source Code Form is subject to the terms of the ApiOpenStudio Public License.
 *            If a copy of the MPL was not distributed with this file,
 *            You can obtain one at https://www.apiopenstudio.com/license/.
 * @author    john89 (https://gitlab.com/john89)
 * @copyright 2020-2030 Naala Pty Ltd
 * @link      https://www.apiopenstudio.com
 */

/* global M */

$(document).ready(function () {
  $('.modal-role-create-trigger').click(function () {
    const modal = $('#modal-role-create')
    modal.modal('open')
  })

  $('.modal-role-edit-trigger').click(function () {
    const modal = $('#modal-role-update')
    const self = $(this)
    modal.find('input[name="rid"]').val(self.attr('rid'))
    modal.find('input[name="name"]').val(self.attr('role-name'))
    M.updateTextFields()
    modal.modal('open')
  })

  $('.modal-role-delete-trigger').click(function () {
    const modal = $('#modal-role-delete')
    const self = $(this)
    modal.find('input[name="rid"]').val(self.attr('rid'))
    modal.find('.role-name').html(self.attr('role-name'))
    modal.modal('open')
  })
})
