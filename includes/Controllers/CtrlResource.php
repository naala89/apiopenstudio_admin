<?php

/**
 * Class CtrlResource.
 *
 * Controller for resources page.
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
use Symfony\Component\Yaml\Yaml;
use Exception;

/**
 * Class CtrlResource.
 *
 * Controller for the resource pages.
 */
class CtrlResource extends CtrlBase
{
    /**
     * {@inheritdoc}
     *
     * @var array Roles permitted to view these pages.
     */
    protected array $permittedRoles = [
        'Developer',
    ];

    /**
     * Sections within a resource file.
     *
     * @var array
     */
    private const META_SECTIONS = [
        'security',
        'process',
        'fragments',
        'output',
    ];

    /**
     * Resources page.
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
            $this->flash->addMessage('error', 'View resources: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();

        // Params for the REST call.
        $allParams = $request->getParams();
        $allParams['order_by'] = empty($allParams['order_by']) ? 'name' : $allParams['order_by'];

        $query = [];
        if (!empty($allParams['filter_by_application'])) {
            $query['appid'] = $allParams['filter_by_application'];
        }
        if (!empty($allParams['keyword'])) {
            $query['keyword'] = $allParams['keyword'];
        }
        if (
            !empty($allParams['order_by'])
                && $allParams['order_by'] != 'account'
                && $allParams['order_by'] != 'application'
        ) {
            $query['order_by'] = $allParams['order_by'];
        }
        if (!empty($allParams['direction'])) {
            $query['direction'] = $allParams['direction'];
        }

        // Fetch the resources.
        $resources = [];
        try {
            $result = $this->apiCall('get', 'resource', [
                'headers' => ['Authorization' => "Bearer {$_SESSION['token']}"],
                'query' => $query,
            ]);
            $resources = json_decode($result->getBody()->getContents(), true);
            $resources = isset($resources['result']) && isset($resources['data']) ? $resources['data'] : $resources;
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        // Pagination.
        $page = $allParams['page'] ?? 1;
        $pages = ceil(count($resources) / $this->settings['admin']['pagination_step']);
        $sortedResources = array_slice(
            $resources,
            ($page - 1) * $this->settings['admin']['pagination_step'],
            $this->settings['admin']['pagination_step'],
            true
        );

        return $this->view->render($response, 'resources.twig', [
            'menu' => $menu,
            'params' => $allParams,
            'resources' => $sortedResources,
            'page' => $page,
            'pages' => $pages,
            'accounts' => $this->userAccounts,
            'applications' => $this->userApplications,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Create a resource page.
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
            $this->flash->addMessage('error', 'Create a resource: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();

        $processors = $this->processorDetails();

        return $this->view->render($response, 'resource.twig', [
            'operation' => 'create',
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $this->userApplications,
            'format' => $args['format'],
            'resource' => !empty($args['resource']) ? $args['resource'] : '',
            'resid' => '',
            'processors' => $processors,
            'messages' => $this->flash->getMessages(),
        ]);
    }

    /**
     * Edit a resource.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function edit(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Edit a resource: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $menu = $this->getMenus();
        $resid = $args['resid'];

        if (empty($args['resource'])) {
            try {
                $result = $this->apiCall('get', 'resource', [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'query' => ['resid' => $resid],
                ]);
                $resource = json_decode($result->getBody()->getContents(), true);
                $resource = isset($resource['result']) && isset($resource['data']) ? $resource['data'] : $resource;
                if (!empty($resource)) {
                    $resource = $resource[0];
                } else {
                    $this->flash->addMessageNow('error', 'resource not found.');
                }
            } catch (Exception $e) {
                $this->flash->addMessageNow('error', $e->getMessage());
            }
        } else {
            $resource = $args['resource'];
        }

        $accounts = $this->userAccounts;
        $applications = $this->userApplications;
        $processors = $this->processorDetails();

        foreach (self::META_SECTIONS as $metaSection) {
            if (isset($resource['meta'][$metaSection])) {
                $resource['meta'][$metaSection] = Yaml::dump($resource['meta'][$metaSection], 500, 2, Yaml::PARSE_OBJECT);
            }
        }

        return $this->view->render($response, 'resource.twig', [
            'operation' => 'edit',
            'menu' => $menu,
            'accounts' => $accounts,
            'applications' => $applications,
            'resource' => !empty($args['resource']) ? $args['resource'] : $resource,
            'processors' => $processors,
            'messages' => $this->flash->getMessages(),
            'resid' => $resid,
        ]);
    }

    /**
     * Upload a resource.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function upload(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Upload a resource: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParsedBody();
        if (
            empty($allPostVars['format'])
            || empty($allPostVars['name'])
            || empty($allPostVars['description'])
            || empty($allPostVars['appid'])
            || empty($allPostVars['method'])
            || empty($allPostVars['uri'])
            || empty($allPostVars['process'])
            || !isset($allPostVars['ttl'])
        ) {
            $this->flash->addMessage('error', 'Cannot upload resource, not all information received');
            return $response->withStatus(302)->withHeader('Location', '/resource/create');
        }
        switch ($allPostVars['format']) {
            case 'yaml':
                $meta = [];
                foreach (self::META_SECTIONS as $item) {
                    if (!empty($allPostVars[$item])) {
                        $meta[$item] = Yaml::parse($allPostVars[$item]);
                    }
                }
                break;
            case 'json':
                $meta = [];
                foreach (self::META_SECTIONS as $item) {
                    $meta[$item] = !empty($allPostVars[$item]) ? json_decode($allPostVars[$item], true) : '';
                }
                break;
            default:
                $meta = '';
                break;
        }

        if (!empty($allPostVars['resid'])) {
            try {
                $this->apiCall('put', 'resource/' . $allPostVars['resid'], [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'name' => $allPostVars['name'],
                        'description' => $allPostVars['description'],
                        'appid' => $allPostVars['appid'],
                        'method' => $allPostVars['method'],
                        'uri' => $allPostVars['uri'],
                        'ttl' => $allPostVars['ttl'],
                        'metadata' => $meta,
                    ],
                ]);
                $this->flash->addMessageNow('info', 'Resource ' . $allPostVars['resid'] . ' successfully edited.');
            } catch (Exception $e) {
                $this->flash->addMessageNow('error', $e->getMessage());
            }
        } else {
            try {
                $this->apiCall('post', 'resource', [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'form_params' => [
                        'name' => $allPostVars['name'],
                        'description' => $allPostVars['description'],
                        'appid' => $allPostVars['appid'],
                        'method' => $allPostVars['method'],
                        'uri' => $allPostVars['uri'],
                        'ttl' => $allPostVars['ttl'],
                        'format' => $allPostVars['format'],
                        'metadata' => $meta,
                    ],
                ]);
                $this->flash->addMessageNow('info', 'Resource successfully created.');
            } catch (Exception $e) {
                $this->flash->addMessageNow('error', $e->getMessage());
            }
        }

        $resource = $this->getResource($allPostVars);
        return !empty($allPostVars['resid']) ?
            $this->edit($request, $response, [
                'format' => $allPostVars['format'],
                'resource' => $resource,
                'resid' => $allPostVars['resid'],
            ]) :
            $this->create($request, $response, [
                'format' => $allPostVars['format'],
                'resource' => $resource,
            ]);
    }

    /**
     * Delete a resource.
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
            $this->flash->addMessage('error', 'Delete a resource: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $allPostVars = $request->getParsedBody();
        $resid = $allPostVars['resid'];

        try {
            $result = $this->apiCall('delete', "resource/$resid", [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            $result = isset($result['result']) && isset($result['data']) ? $result['data'] : $result;
            if ($result == 'true') {
                $this->flash->addMessage('info', 'Resource successfully deleted.');
            } else {
                $this->flash->addMessage('error', 'Resource failed to delete, please check the logs.');
            }
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withStatus(302)->withHeader('Location', '/resources');
        }

        return $response->withStatus(302)->withHeader('Location', '/resources');
    }

    /**
     * Download a resource.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function download(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Download a resource: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        if (empty($args['resid'])) {
            $this->flash->addMessage('error', 'Missing resource ID argument');
            return $response->withStatus(302)->withHeader('Location', '/resources');
        }
        if (empty($args['format']) || !in_array($args['format'], ['yaml', 'json'])) {
            $this->flash->addMessage('error', 'Invalid resource format requested.');
            return $response->withStatus(302)->withHeader('Location', '/resources');
        }

        try {
            $result = $this->apiCall(
                'get',
                "resource/export/{$args['format']}/{$args['resid']}",
                [
                    'headers' => ['Authorization' => "Bearer {$_SESSION['token']}"],
                ]
            );
        } catch (Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withStatus(302)->withHeader('Location', '/resources');
        }

        $result = $result->getBody()->getContents();
        $test = json_decode($result, true);
        $result = is_array($test) && isset($test['result']) && isset($test['data']) ? $test['data'] : $result;
        $result = is_array($result) ? json_encode($result) : $result;
        echo trim($result, '"');
        return $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="apiopenstudio.' . $args['format'] . '"');
    }

    /**
     * Import a resource.
     *
     * @param Request $request Request object.
     * @param Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface Response.
     */
    public function import(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Import a resource: access denied');
            return $response->withStatus(302)->withHeader('Location', '/');
        }

        $directory = $this->settings['admin']['dir_tmp'];
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['resource_file'];

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            try {
                $filename = $this->moveUploadedFile($directory, $uploadedFile);
                $this->apiCall('post', 'resource/import', [
                    'headers' => [
                        'Authorization' => "Bearer " . $_SESSION['token'],
                        'Accept' => 'application/json',
                    ],
                    'multipart' => [
                        [
                            'name' => 'resource_file',
                            'contents' => fopen($directory . $filename, 'r'),
                        ],
                    ],
                ]);
            } catch (Exception $e) {
                $this->flash->addMessage('error', $e->getMessage());
            }
        } else {
            $this->flash->addMessage('error', 'Error in uploading file');
        }
        unlink($directory . $filename);

        return $response->withStatus(302)->withHeader('Location', '/resources');
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

    /**
     * Generate the array for Twig for the current resource to be created/edited.
     *
     * @param array $allPostVars Post vars in this request.
     *
     * @return array Twig vars.
     */
    private function getResource(array $allPostVars): array
    {
        $arr = [
            'name' => $allPostVars['name'],
            'description' => $allPostVars['description'],
            'appid' => $allPostVars['appid'],
            'method' => $allPostVars['method'],
            'uri' => $allPostVars['uri'],
            'ttl' => $allPostVars['ttl'],
            'meta' => [
                'security' =>  $allPostVars['security'],
                'process' =>  $allPostVars['process'],
                'fragments' =>  $allPostVars['fragments'],
                'output' =>  $allPostVars['output'],
            ]
        ];
        if (isset($allPostVars['resid'])) {
            $arr['resid'] = $allPostVars['resid'];
        }
        return $arr;
    }

    /**
     * Fetch details of all available processors.
     *
     * @return array Processors and their details.
     */
    protected function processorDetails(): array
    {
        $processors = [];
        try {
            $result = $this->apiCall('get', 'processors/all', [
                'headers' => [
                    'Authorization' => "Bearer {$_SESSION['token']}",
                    'Accept' => 'application/json',
                ],
            ]);
            $processors = json_decode($result->getBody()->getContents(), true);
            $processors = isset($processors['result']) && isset($processors['data']) ? $processors['data'] : $processors;
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        $sortedProcessors = [];
        foreach ($processors as $processor) {
            $sortedProcessors[$processor['menu']][] = $processor;
        }

        return $sortedProcessors;
    }
}
