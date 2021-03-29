/**
 * JS for the admin application page.
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
   * Edit application modal.
   */
  $('.modal-app-edit-trigger').click(function () {
    const modal = $('#modal-app-edit')
    const accid = $(this).attr('accid')
    const appid = $(this).attr('appid')
    const name = $(this).attr('name')
    const selectAccid = modal.find('select[name="edit-app-accid"]')

    selectAccid.find('option[value="' + accid + '"]').prop('selected', true)
    selectAccid.formSelect()
    modal.find('input[name="edit-app-appid"]').val(appid)
    modal.find('input[name="edit-app-name"]').val(name)
    modal.modal('open')
  })

  /**
   * Delete application modal.
   */
  $('.modal-app-delete-trigger').click(function () {
    const modal = $('#modal-app-delete')
    const appid = $(this).attr('appid')
    const name = $(this).attr('name')
    modal.find('input[name="delete-app-appid"]').val(appid)
    modal.find('#delete-app-name').html(name)
    modal.modal('open')
  })
})
