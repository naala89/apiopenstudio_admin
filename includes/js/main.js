/**
 * Generic JS for the admin pages.
 *
 * @package   ApiOpenStudioAdmin
 * @license   This Source Code Form is subject to the terms of the ApiOpenStudio Public License.
 *            If a copy of the MPL was not distributed with this file,
 *            You can obtain one at https://www.apiopenstudio.com/license/.
 * @author    john89 (https://gitlab.com/john89)
 * @copyright 2020-2030 Naala Pty Ltd
 * @link      https://www.apiopenstudio.com
 */

/* global M, APIOPENSTUDIO */

$(document).ready(function () {
  M.AutoInit()

  /**
   * Set the appAccMap object.
   * This maps applications to accounts.
   */
  APIOPENSTUDIO.createAppAccMap = () => {
    APIOPENSTUDIO.appAccMap = []
    APIOPENSTUDIO.applications.forEach(function (application, key) {
      APIOPENSTUDIO.appAccMap[key] = {
        accid: application.accid,
        name: application.name
      }
    })
  }

  /**
   * Reset options in applications to all.
   *
   * @param string selector
   *   Applications element selector.
   */
  APIOPENSTUDIO.resetApplications = function (selector) {
    const selectApp = $(selector)
    selectApp.find('option').remove()
    selectApp.append($('<option>', { value: '', text: 'Please select' }))
    APIOPENSTUDIO.appAccMap.forEach(function (application, appid) {
      $(selector).append($('<option>', { value: appid, text: application.name }))
    })
    selectApp.val('')
    selectApp.formSelect()
  }

  /**
   * Update an account selector based on application ID
   *
   * @param integer appid
   *   Application ID.
   * @param string selector
   *   JQuery selector for the account select element.
   */
  APIOPENSTUDIO.setAccount = function (appid, selector) {
    const selectAcc = $(selector)
    if (typeof APIOPENSTUDIO.appAccMap[appid] === 'undefined') {
      selectAcc.val('')
    } else {
      selectAcc.val(APIOPENSTUDIO.appAccMap[appid].accid)
    }
    selectAcc.formSelect()
  }

  /**
   * Update an application selector based on account ID
   *
   * @param integer accid
   *   Account ID
   * @param string selector
   *   JQuery selector for the application select element.
   */
  APIOPENSTUDIO.setApplicationOptions = function (accid, selector) {
    const selectApp = $(selector)
    selectApp.find('option').remove()
    selectApp.append($('<option>', { value: '', text: 'Please select' }))
    APIOPENSTUDIO.appAccMap.forEach(function (application, appid) {
      /* eslint-disable eqeqeq */
      if (accid == application.accid) {
        selectApp.append($('<option>', { value: appid, text: application.name }))
      }
    })
    selectApp.val('')
    selectApp.formSelect()
  }

  /**
   * Close alert panel.
   */
  $('.close-apiopenstudio-alert').click(function () {
    $(this).closest('.apiopenstudio-alert').fadeOut('slow', function () {
    })
  })

  APIOPENSTUDIO.createAppAccMap()
})
