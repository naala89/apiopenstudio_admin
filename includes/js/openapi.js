/**
 * JS for the admin resource page.
 *
 * @package   ApiOpenStudioAdmin
 * @license   This Source Code Form is subject to the terms of the ApiOpenStudio Public License.
 *            If a copy of the MPL was not distributed with this file,
 *            You can obtain one at https://www.apiopenstudio.com/license/.
 * @author    john89 (https://gitlab.com/john89)
 * @copyright 2020-2030 Naala Pty Ltd
 * @link      https://www.apiopenstudio.com
 */

/* global APIOPENSTUDIO, ace */

$(document).ready(function () {
  APIOPENSTUDIO.generateOpenApi = () => {
    const params = new URLSearchParams(location.search)
    const appid = parseInt(params.get('appid'))
    const generateForm = $('#generate-schema')
    generateForm.find('input[name="appid"]').val(appid)
    generateForm.submit()
  }

  $('input[name="resource_file"]').change(function () {
    $(this).closest('form').submit()
  })

  APIOPENSTUDIO.uploadOpenApi = () => {
    const params = new URLSearchParams(location.search)
    const appid = parseInt(params.get('appid'))
    const editor = ace.edit('ace-editor')
    const uploadForm = $('#upload-schema')
    const schema = editor.getValue()

    uploadForm.attr('action', '/open-api/edit?appid=' + appid)
    uploadForm.find('input[name="schema"]').val(schema)
    uploadForm.submit()
  }

  $('input[name="openapi_file"]').change(function () {
    $(this).closest('form').submit()
  })
})
