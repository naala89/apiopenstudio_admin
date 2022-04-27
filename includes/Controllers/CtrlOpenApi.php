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
use http\Params;
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
     * @param Request $request Request object.
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
        $schema = '';
        if (!empty($getParams['appid'])) {
            try {
                $result = $this->apiCall('get', 'openapi', [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'query' => ['appid' => $getParams['appid']],
                ]);
                $result = json_decode($result->getBody()->getContents(), true);
                $schema = isset($result['result']) && isset($result['data']) ? $result['data'] : $result;
            } catch (Exception $e) {
                $this->flash->addMessageNow('error', $e->getMessage());
                $schema = [];
            }
        }

        $menu = $this->getMenus();

        return $this->view->render($response, 'open-api.twig', [
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $this->userApplications,
            'appid' => $getParams['appid'],
            'schema' => json_encode($schema),
            'roles' => $this->userRoles,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * OpenApi editor page.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function edit(Request $request, Response $response, array $args): ResponseInterface
    {
        $this->permittedRoles = [
            'Developer',
        ];
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $requestParams = $request->getParams();
        $appid = $requestParams['appid'] ?? false;

        $schema = '';

        // Post so we upload.
        if ($appid && $request->isPost()) {
            $yaml = $requestParams['schema'];
            $json = $this->fixYamlEmptyObjectToEmptyArray(json_encode(Yaml::parse($yaml),JSON_UNESCAPED_SLASHES));

            try {
                $result = $this->apiCall('put', "openapi/$appid", [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'body' => $json,
                ]);
                $result = json_decode($result->getBody()->getContents(), true);
                $schema = isset($result['result']) && isset($result['data']) ? $result['data'] : $result;
            } catch (Exception $e) {
                $this->flash->addMessageNow('error', $e->getMessage());
                $schema = Yaml::dump(Yaml::parse($yaml));
            }
        }

        // Not upload, so fetch existing schema from API.
        if ($appid && empty($schema)) {
            $result = $this->apiCall('get', 'openapi', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => ['appid' => $appid],
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            $schema = isset($result['result']) && (isset($result['data']) || $result['data'] === null) ? $result['data'] : $result;
            $schema = $schema ?? '[]';
            $schema = $this->fixYamlEmptyObjectToEmptyArray(json_encode($schema));
        }

        $menu = $this->getMenus();

        return $this->view->render($response, 'open-api-edit.twig', [
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $this->userApplications,
            'appid' => $appid,
            'schema' => $schema,
            'roles' => $this->userRoles,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Yaml::parse will convert to empty objects to empty array...
     *
     * @param string $json
     *
     * @return string
     */
    protected function fixYamlEmptyObjectToEmptyArray(string $json): string
    {
        $emptyArrShouldBeObj = ['additionalProperties', 'content', 'items', 'paths'];
        foreach ($emptyArrShouldBeObj as $key) {
            $json = str_replace("\"$key\":[]", "\"$key\":{}", $json);
        }
        return $json;
    }

    /**
     * Upload OpenApi schema.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function default(Request $request, Response $response, array $args): ResponseInterface
    {
        $this->permittedRoles = [
            'Developer',
        ];
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParams();
        $appid = $allPostVars['appid'];

        try {
            $this->apiCall('post', "openapi/default/$appid", [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', "/open-api/edit?appid=$appid");
    }

    /**
     * Upload OpenApi schema.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function import(Request $request, Response $response, array $args): ResponseInterface
    {
        $this->permittedRoles = [
            'Developer',
        ];
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        if ($request->isPost()) {
            $directory = $this->settings['admin']['dir_tmp'];
            $uploadedFiles = $request->getUploadedFiles();
            $uploadedFile = $uploadedFiles['openapi_file'];

            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

                try {
                    $filename = $this->moveUploadedFile($directory, $uploadedFile);
                    $result = $this->apiCall('post', 'openapi/import', [
                        'headers' => [
                            'Authorization' => "Bearer " . $_SESSION['token'],
                            'Accept' => 'application/json',
                        ],
                        'multipart' => [
                            [
                                'name' => 'openapi',
                                'contents' => fopen($directory . $filename, 'r'),
                            ],
                        ],
                    ]);
                    $results = json_decode($result->getBody()->getContents(), true);
                    $updatedResources = $newResources = [];
                    foreach ($results['updated'] as $updated) {
                        $updatedResources[] = '[' . strtoupper($updated['method']) . '] '
                        . $updated['account']
                        . '/' . $updated['application']
                        . '/' . $updated['uri'];
                    }
                    foreach ($results['new'] as $new) {
                        $newResources[] = '[' . strtoupper($new['method']) . '] '
                        . $new['account']
                        . '/' . $new['application']
                        . '/' . $new['uri'];
                    }
                    if (!empty($updatedResources)) {
                        $this->flash->addMessageNow(
                            'info',
                            'OpenApi updated for resources:<br/><br />' . implode('<br/>', $updatedResources)
                        );
                    }
                    if (!empty($newResources)) {
                        $this->flash->addMessageNow(
                            'info',
                            'Created new ApiOpenStudio stubs for resources:<br/><br />' . implode('<br/>', $newResources)
                        );
                    }
                } catch (Exception $e) {
                    $this->flash->addMessageNow('error', $e->getMessage());
                }
            } else {
                $this->flash->addMessageNow('error', 'Error in uploading file');
            }
            unlink($directory . $filename);
        }

        $menu = $this->getMenus();
        return $this->view->render($response, 'open-api-import.twig', [
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $this->userApplications,
            'roles' => $this->userRoles,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param string $directory Directory to which the file is moved.
     * @param mixed $uploadedFile Uploaded file to move.
     *
     * @return string filename of moved file.
     */
    private function moveUploadedFile(string $directory, $uploadedFile): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        try {
            $basename = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            $this->flash->addMessageNow($e->getMessage());
        }
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . $filename);

        return $filename;
    }
}
