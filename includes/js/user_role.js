/**
 * JS for the admin user role page.
 *
 * @package   ApiOpenStudioAdmin
 * @license   This Source Code Form is subject to the terms of the
 *            Mozilla Public License, v. 2.0.
 *            If a copy of the MPL was not distributed with this file,
 *            You can obtain one at https://mozilla.org/MPL/2.0/.
 * @author    john89 (https://gitlab.com/john89)
 * @copyright 2020-2030 Naala Pty Ltd
 * @link      https://www.apiopenstudio.com
 */

/* global APIOPENSTUDIO */

$(document).ready(function () {
  /**
   * User role create - role select.
   */
  $('#modal-user-role-create select[name=\'rid\']').on('change', function () {
    const selected = $(this).find('option:selected').text()
    const modal = $('#modal-user-role-create')
    const selectAccid = modal.find('select[name=\'accid\']')
    const selectAppid = modal.find('select[name=\'appid\']')
    if (selected === 'Administrator') {
      selectAccid.val('').prop('disabled', true)
      selectAccid.formSelect()
      selectAppid.val('').prop('disabled', true)
      selectAppid.formSelect()
    } else if (selected === 'Account manager') {
      selectAccid.prop('disabled', false)
      selectAccid.formSelect()
      selectAppid.val('').prop('disabled', true)
      selectAppid.formSelect()
    } else {
      selectAccid.prop('disabled', false)
      selectAccid.formSelect()
      selectAppid.prop('disabled', false)
      selectAppid.formSelect()
    }
  })

  /**
   * User role create - account select.
   */
  $('#modal-user-role-create select[name=\'accid\']').on('change', function () {
    APIOPENSTUDIO.setApplicationOptions(
      $(this).val(),
      '#modal-user-role-create select[name="appid"]'
    )
  })

  /**
   * User role delete.
   */
  $('.modal-user-role-delete-trigger').on('click', function () {
    const urid = $(this).attr('urid')
    const user = $(this).attr('user')
    const account = $(this).attr('acc')
    const application = $(this).attr('app')
    const role = $(this).attr('role')
    const modal = $('#modal-user-role-delete')
    modal.find('input[name=\'urid\']').val(urid)
    modal.find('.user').html(user)
    modal.find('.acc').html(account)
    modal.find('.app').html(application)
    modal.find('.role').html(role)
    modal.modal('open')
  })
})
