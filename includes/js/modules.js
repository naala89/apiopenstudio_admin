/**
 * JS for the modules page.
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
   * Install module modal.
   */
  $('.modal-module-install-trigger').click(function () {
    const modal = $('#modal-module-install')
    const moduleName = $(this).attr('module_name')
    const machineName = $(this).attr('machine_name')
    modal.find('input[name="machine_name"]').val(machineName)
    modal.find('span.module-name').html(moduleName)
    modal.modal('open')
  })

  /**
   * Uninstall module modal.
   */
  $('.modal-module-uninstall-trigger').click(function () {
    const modal = $('#modal-module-uninstall')
    const moduleName = $(this).attr('module_name')
    const machineName = $(this).attr('machine_name')
    modal.find('input[name="machine_name"]').val(machineName)
    modal.find('span.module-name').html(moduleName)
    modal.modal('open')
  })

  /**
   * Update module modal.
   */
  $('.modal-module-update-trigger').click(function () {
    const modal = $('#modal-module-update')
    const moduleName = $(this).attr('module_name')
    const machineName = $(this).attr('machine_name')
    modal.find('input[name="machine_name"]').val(machineName)
    modal.find('span.module-name').html(moduleName)
    modal.modal('open')
  })

  /**
   * Composer require modal.
   */
  $('.modal-composer-require-trigger').click(function () {
    const modal = $('#modal-composer-require')
    const packageName = $('#package-name').val()
    if (packageName !== '') {
      modal.find('input[name="package"]').val(packageName)
      modal.find('span.package-name').html(packageName)
      modal.modal('open')
    }
  })

  /**
   * Composer remove modal.
   */
  $('.modal-composer-remove-trigger').click(function () {
    const modal = $('#modal-composer-remove')
    const packageName = $('#package-name').val()
    if (packageName !== '') {
      modal.find('input[name="package"]').val(packageName)
      modal.find('span.package-name').html(packageName)
      modal.modal('open')
    }
  })
})
