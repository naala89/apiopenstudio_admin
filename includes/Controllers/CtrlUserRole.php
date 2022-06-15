<?php

/**
 * Class CtrlUserRole.
 *
 * Controller for user/role page.
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

use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class CtrlUserRole.
 *
 * Controller for the user role pages.
 */
class CtrlUserRole extends CtrlBase
{
    /**
     * {@inheritdoc}
     *
     * @var array Roles permitted to view these pages.
     */
    protected array $permittedRoles = [
        'Administrator',
        'Account manager',
        'Application manager',
    ];

    /**
     * List user roles.
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
            $this->flash->addMessage('error', 'View user roles: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $params = $request->getQueryParams();
        $token = $_SESSION['token'];

        try {
            $result = $this->apiCall('get', 'user/role', [
                'headers' => ['Authorization' => "Bearer $token"],
                'query' => $params,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            $userRoles = isset($result['result']) && isset($result['data']) ? $result['data'] : $result;

            $result = $this->apiCall('get', 'user', [
                'headers' => ['Authorization' => "Bearer $token"],
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            $users = isset($result['result']) && isset($result['data']) ? $result['data'] : $result;
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
            return $this->view->render($response, 'user-roles.twig', [
                'menu' => $menu,
                'user_roles' => [],
                'messages' => $this->flash->getMessages(),
            ]);
        }

        $sortedUsers = [];
        foreach ($users as $user) {
            $sortedUsers[$user['uid']] = $user;
        }
        $sortedAccounts = [];
        foreach ($this->userAccounts as $account) {
            $sortedAccounts[$account['accid']] = $account;
        }

        return $this->view->render($response, 'user-roles.twig', [
            'menu' => $menu,
            'params' => $params,
            'user_roles' => $userRoles,
            'accounts' => $sortedAccounts,
            'applications' => $this->userApplications,
            'users' => $sortedUsers,
            'roles' => $this->allRoles,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Create a user role.
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
            $this->flash->addMessage('error', 'Create user role: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParsedBody();
        $token = $_SESSION['token'];

        try {
            $payload = [
                'uid' => $allPostVars['uid'],
                'rid' => $allPostVars['rid'],
            ];
            if (!empty($allPostVars['accid'])) {
                $payload['accid'] = $allPostVars['accid'];
            }
            if (!empty($allPostVars['appid'])) {
                $payload['appid'] = $allPostVars['appid'];
            }
            $this->apiCall('post', 'user/role', [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ],
                'form_params' => $payload,
            ]);
            $this->flash->addMessage('info', 'User role created.');
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', '/user/roles');
    }

    /**
     * Delete a user role.
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

        $allPostVars = $request->getParsedBody();
        $urid = $allPostVars['urid'];
        if (empty($urid)) {
            $this->flash->addMessage('error', 'Cannot delete user role, user role unspecified.');
            return $response->withStatus(302)->withHeader('Location', '/user/roles');
        }

        $token = $_SESSION['token'];

        try {
            $this->apiCall('delete', 'user/role/' . $urid, [
                'headers' => ['Authorization' => "Bearer $token"],
            ]);
            $this->flash->addMessage('info', 'User role deleted.');
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', '/user/roles');
    }
}
