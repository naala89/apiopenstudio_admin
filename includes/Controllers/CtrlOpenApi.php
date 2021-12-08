<?php

/**
 * Class CtrlOpenApi.
 *
 * Controller for OpenApi page.
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
use Symfony\Component\Yaml\Yaml;

/**
 * Class CtrlOpenApi.
 *
 * Controller for the OpenApi page.
 */
class CtrlOpenApi extends CtrlBase
{
    /**
     * {@inheritdoc}
     *
     * @var array Roles permitted to view these pages.
     */
    protected array $permittedRoles = [];

    /**
     * OpenApi documentation page.
     *
     * @param \Slim\Http\Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function index(Request $request, Response $response, array $args): ResponseInterface
    {
        $this->permittedRoles = [];
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access denied');
            return $response->withStatus(302)->withHeader('Location', '/logout');
        }

        $getParams = $request->getParams();
        $openApiDef = '';
        if (!empty($getParams['appid'])) {
            $result = $this->apiCall(
                'get',
                'openapi',
                [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'query' => ['appid' => $getParams['appid']],
                ]
            );
            $openApiDef = $result->getBody()->getContents();
        }

        $menu = $this->getMenus();
        $userApplications = $this->userApplications;
        if (in_array('Developer', $this->userRoles)) {
            $userApplications[] = ['appid' => 1, 'name' => 'Core'];
        }

        return $this->view->render($response, 'open-api.twig', [
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $userApplications,
            'appid' => $getParams['appid'],
            'openapi_def' => $openApiDef,
            'roles' => $this->userRoles,
            'flash' => $this->flash,
        ]);
    }

    /**
     * OpenApi editor page.
     *
     * @param \Slim\Http\Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function editor(Request $request, Response $response, array $args): ResponseInterface
    {
        $this->permittedRoles = [
            'Developer',
        ];
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $getParams = $request->getParams();
        $openApiDef = '';
        if (!empty($getParams['appid'])) {
            $result = $this->apiCall(
                'get',
                'openapi',
                [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'query' => ['appid' => $getParams['appid']],
                ]
            );
            $openApiDef = $result->getBody()->getContents();
        }

//        $openApiDef = YAML::dump(json_decode($openApiDef));

        $menu = $this->getMenus();
        $userApplications = $this->userApplications;
        if (in_array('Developer', $this->userRoles)) {
            $userApplications[] = ['appid' => 1, 'name' => 'Core'];
        }

        return $this->view->render($response, 'open-api-editor.twig', [
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $userApplications,
            'appid' => $getParams['appid'],
            'openapi_def' => $openApiDef,
            'roles' => $this->userRoles,
            'flash' => $this->flash,
        ]);
    }

    /**
     * Upload OpenApi schema.
     *
     * @param \Slim\Http\Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function upload(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access admin: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $appid = $allPostVars['appid'];
        $json = Yaml::parse($allPostVars['schema']);

        try {
            $result = $this->apiCall(
                'put',
                "openapi/$appid",
                [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'json' => $json,
                ]
            );
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', "/open-api/edit?appid=$appid");
    }
}
