<?php

/**
 * Class CtrlAccount.
 *
 * Controller for account page.
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
 * Class CtrlAccount.
 *
 * Controller for the account page.
 */
class CtrlAccount extends CtrlBase
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
     * Accounts page.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface|Response Response.
     */
    public function index(Request $request, Response $response, array $args)
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'View accounts: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();

        // Filter params and current page.
        $allParams = $request->getParams();
        $params = [];
        if (!empty($allParams['keyword'])) {
            $params['keyword'] = $allParams['keyword'];
        }
        $params['order_by'] = 'name';
        $params['direction'] = isset($allParams['direction']) ? $allParams['direction'] : 'asc';
        $page = $allParams['page'] ?? 1;

        $accounts = $this->apiCallAccountAll($params);

        // Get total number of pages and current page's accounts to display.
        $pages = ceil(count($accounts) / $this->settings['admin']['pagination_step']);
        $accounts = array_slice(
            $accounts,
            ($page - 1) * $this->settings['admin']['pagination_step'],
            $this->settings['admin']['pagination_step'],
            true
        );

        return $this->view->render($response, 'accounts.twig', [
            'keyword' => $params['keyword'] ?? '',
            'direction' => strtoupper($params['direction']),
            'page' => $page,
            'pages' => $pages,
            'menu' => $menu,
            'accounts' => $accounts,
            'isAdmin' => in_array('Administrator', $this->userRoles),
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Create an account.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return Response Response.
     */
    public function create(Request $request, Response $response, array $args): Response
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'View accounts: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        // Validate the input.
        $allPostVars = $request->getParsedBody();
        if (empty($name = $allPostVars['name'])) {
            $this->flash->addMessage('error', 'Cannot create account, no name defined.');
            return $response->withRedirect('/accounts');
        }
        // Create the new account.
        try {
            $result = $this->apiCall(
                'post',
                'account',
                [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'form_params' => [
                        'name' => $name,
                    ],
                ]
            );
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && isset($result['data'])) {
                $result = $result['data'];
            }
            if (isset($result['accid']) && isset($result['name'])) {
                $this->flash->addMessage('info', "Account {$result['name']} (accid: {$result['accid']}) created");
            }
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', '/accounts');
    }

    /**
     * Edit an account.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return Response Response.
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'View accounts: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        // Validate the input.
        $allPostVars = $request->getParsedBody();
        if (empty($accid = $allPostVars['accid']) || empty($name = $allPostVars['name'])) {
            $this->flash->addMessage('error', 'Cannot edit account, invalid accid or name.');
            return $response->withRedirect('/accounts');
        }

        // Edit the account.
        try {
            $this->apiCall(
                'put',
                "account/$accid/" . urlencode($name),
                [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'form_params' => [
                        'name' => $name,
                    ],
                ]
            );
            $this->flash->addMessage('info', "Account '$accid' updated to '$name'.");
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }
        return $response->withStatus(302)->withHeader('Location', '/accounts');
    }

    /**
     * Delete an account.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return Response Response.
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'View accounts: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        // Validate the input.
        $allPostVars = $request->getParsedBody();
        if (empty($accid = $allPostVars['accid'])) {
            $this->flash->addMessage('error', 'Cannot delete account, no accid defined.');
            return $response->withRedirect('/accounts');
        }

        try {
            $result = $this->apiCall(
                'delete',
                "account/$accid",
                [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $this->flash->addMessage('info', "Account '$accid' deleted.");
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }
        return $response->withStatus(302)->withHeader('Location', '/accounts');
    }
}
