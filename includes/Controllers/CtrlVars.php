<?php

/**
 * Class CtrlVars.
 *
 * Controller for variables page.
 *
 * @package    ApiOpenStudioAdmin
 * @subpackage Controllers
 * @author     john89 (https://gitlab.com/john89)
 * @copyright  2020-2030 Naala Pty Ltd
 * @license    This Source Code Form is subject to the terms of the ApiOpenStudio Public License.
 *             If a copy of the MPL was not distributed with this file,
 *             You can obtain one at https://www.apiopenstudio.com/license/.
 * @link       https://www.apiopenstudio.com
 */

namespace ApiOpenStudioAdmin\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class CtrlVars.
 *
 * Controller for the vars pages.
 */
class CtrlVars extends CtrlBase
{

    /**
     * Roles allowed to visit the page.
     *
     * @var array
     */
    protected array $permittedRoles = [
        'Administrator',
        'Account manager',
        'Application manager',
        'Developer',
    ];

    /**
     * List vars
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function index(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access vars: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $allParams = $request->getParams();
        $query = [];
        $query['appid'] = !empty($allParams['filter_by_application']) ? $allParams['filter_by_application'] : '';
        $query['accid'] = !empty($allParams['filter_by_account']) ? $allParams['filter_by_account'] : '';
        $query['keyword'] = !empty($allParams['keyword']) ? $allParams['keyword'] : '';
        $query['order_by'] = !empty($allParams['order_by']) ? $allParams['order_by'] : '';
        $query['direction'] = !empty($allParams['direction']) ? $allParams['direction'] : '';

        try {
            $result = $this->apiCall('get', 'var_store', [
                'headers' => [
                    'Authorization' => "Bearer {$_SESSION['token']}",
                    'Accept' => 'application/json',
                ],
                'query' => $query,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            $vars = isset($result['result']) && isset($result['data']) ? $result['data'] : $result;
        } catch (\Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
            $vars = [];
        }

        // Pagination.
        $page = isset($params['page']) ? $allParams['page'] : 1;
        $pages = ceil(count($vars) / $this->settings['admin']['pagination_step']);
        $vars = array_slice(
            $vars,
            ($page - 1) * $this->settings['admin']['pagination_step'],
            $this->settings['admin']['pagination_step'],
            true
        );

        return $this->view->render($response, 'vars.twig', [
            'menu' => $menu,
            'vars' => $vars,
            'applications' => $this->userApplications,
            'accounts' => $this->userAccounts,
            'params' => $allParams,
            'page' => $page,
            'pages' => $pages,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Create var
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function create(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access admin: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $allPostVars = $request->getParams();
        $params = [];
        $params['appid'] = !empty($allPostVars['create-var-appid']) ? $allPostVars['create-var-appid'] : null;
        $params['accid'] = !empty($allPostVars['create-var-accid']) ? $allPostVars['create-var-accid'] : null;
        $params['key'] = !empty($allPostVars['create-var-key']) ? $allPostVars['create-var-key'] : null;
        $params['val'] = !empty($allPostVars['create-var-val']) ? $allPostVars['create-var-val'] : null;

        try {
            $this->apiCall('post', 'var_store', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'form_params' => $params,
            ]);
            $this->flash->addMessageNow('info', 'Var successfully created.');
        } catch (\Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Update var
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function update(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access admin: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $allPostVars = $request->getParams();
        $params = [];
        if (isset($allPostVars['edit-var-vid'])) {
            $params['vid'] = $allPostVars['edit-var-vid'];
        }
        if (isset($allPostVars['edit-var-val'])) {
            $params['val'] = $allPostVars['edit-var-val'];
        }

        try {
            $this->apiCall('put', 'var_store/' . $params['vid'], [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'body' => $params['val'],
            ]);
            $this->flash->addMessageNow('info', 'Var successfully updated.');
        } catch (\Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Delete var
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function delete(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access admin: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $allPostVars = $request->getParams();
        $vid = isset($allPostVars['delete-var-vid']) ? $allPostVars['delete-var-vid'] : '';

        if (empty($vid)) {
            $this->flash->addMessageNow('error', 'Var not deleted, no vid received.');
            return $this->index($request, $response, $args);
        }

        try {
            $result = $this->apiCall('delete', "var_store/$vid", [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
            ]);
            $this->flash->addMessageNow('info', "Var ($vid) successfully deleted.");
        } catch (\Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }
}
