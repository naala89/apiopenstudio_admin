<?php

/**
 * Class CtrlModules.
 *
 * Controller for modules page.
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
 * Class CtrlModules.
 *
 * Controller for the vars pages.
 */
class CtrlModules extends CtrlBase
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
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $allParams = $request->getParams();

        try {
            $ModuleResult = $this->apiCall('get', 'modules', [
                'headers' => [
                    'Authorization' => "Bearer {$_SESSION['token']}",
                    'Accept' => 'application/json',
                ]
            ]);
            $ModuleResult = json_decode($ModuleResult->getBody()->getContents(), true);
            $modules = isset($ModuleResult['result']) && isset($ModuleResult['data']) ? $ModuleResult['data'] : $ModuleResult;
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
            $modules = [];
        }

        try {
            $RepositoryResult = $this->apiCall('get', 'composer/repository', [
                'headers' => [
                    'Authorization' => "Bearer {$_SESSION['token']}",
                    'Accept' => 'application/json',
                ]
            ]);
            $RepositoryResult = json_decode($RepositoryResult->getBody()->getContents(), true);
            $repositories = isset($RepositoryResult['result']) && isset($RepositoryResult['data']) ? $RepositoryResult['data'] : $RepositoryResult;
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
            $repositories = [];
        }

        // Pagination.
        $page = isset($params['page']) ? $allParams['page'] : 1;
        $pages = ceil(count($modules) / $this->settings['admin']['pagination_step']);
        $modules = array_slice(
            $modules,
            ($page - 1) * $this->settings['admin']['pagination_step'],
            $this->settings['admin']['pagination_step'],
            true
        );

        return $this->view->render($response, 'modules.twig', [
            'menu' => $menu,
            'modules' => $modules,
            'repositories' => $repositories,
            'page' => $page,
            'pages' => $pages,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Install module
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function install(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $payload = [];
        $payload['machine_name'] = !empty($allPostVars['machine_name']) ? $allPostVars['machine_name'] : null;

        try {
            $result = $this->apiCall('post', 'modules', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'form_params' => $payload,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Uninstall module
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function uninstall(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $params = [];
        $params['machine_name'] = !empty($allPostVars['machine_name']) ? $allPostVars['machine_name'] : null;

        try {
            $result = $this->apiCall('delete', 'modules', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $params,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Update module
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
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $params = [];
        $params['machine_name'] = !empty($allPostVars['machine_name']) ? $allPostVars['machine_name'] : null;

        try {
            $result = $this->apiCall('put', 'modules', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $params,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Composer require
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function require(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $payload = [];
        $payload['package'] = !empty($allPostVars['package']) ? $allPostVars['package'] : null;

        try {
            $result = $this->apiCall('post', 'composer', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'form_params' => $payload,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Composer remove
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function remove(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $params = [];
        $params['package'] = !empty($allPostVars['package']) ? $allPostVars['package'] : null;

        try {
            $result = $this->apiCall('delete', 'composer', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $params,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Composer repository add
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function repoAdd(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $payload = [];
        $payload['repository_key'] = !empty($allPostVars['repository-key']) ? $allPostVars['repository-key'] : null;
        $payload['repository_url'] = !empty($allPostVars['repository-url']) ? $allPostVars['repository-url'] : null;

        try {
            $result = $this->apiCall('post', 'composer/repository', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'form_params' => $payload,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }

    /**
     * Composer repository remove
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function repoRemove(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access modules: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $params = [];
        $params['repository_key'] = !empty($allPostVars['repository_key']) ? $allPostVars['repository_key'] : null;

        try {
            $result = $this->apiCall('delete', 'composer/repository', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $params,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            $this->flash->addMessageNow('info', $result);
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $this->index($request, $response, $args);
    }
}
