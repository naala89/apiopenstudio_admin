<?php

/**
 * Class CtrlBase.
 *
 * Parent class for all controllers.
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
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Slim\Collection;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CtrlBase.
 *
 * Base controller for the all ApiOpenStudio PHP Slim pages.
 */
class CtrlBase
{

    /**
     * Roles allowed to visit the page.
     *
     * @var array
     */
    protected array $permittedRoles = [];

    /**
     * Setting class.
     *
     * @var Slim\Collection
     */
    protected $settings;

    /**
     * Twig class.
     *
     * @var Twig
     */
    protected Twig $view;

    /**
     * Flash messages object.
     *
     * @var \Slim\Flash\Messages.
     */
    protected Messages $flash;

    /**
     * Menu items available to the user.
     *
     * @var array.
     */
    protected array $menu;

    /**
     * Array of all roles the user has.
     *
     * @var array
     */
    protected array $allRoles = [];

    /**
     * Array of the user access rights.
     *
     * @var array
     *
     * [
     *      accid => [
     *          appid => [
     *              rid
     *          ]
     *      ]
     * ]
     */
    protected array $userAccessRights = [];

    /**
     * Array of oles the user has.
     *
     * @var array
     */
    protected array $userRoles = [];

    /**
     * Array of accounts the user has access to.
     *
     * @var array
     */
    protected array $userAccounts = [];

    /**
     * Array of applications the user has access to.
     *
     * @var array
     */
    protected array $userApplications = [];

    /**
     * Base constructor.
     *
     * @param Collection $settings Settings array.
     * @param Twig $view View container.
     * @param Messages $flash Flash messages container.
     */
    public function __construct(Collection $settings, Twig $view, Messages $flash)
    {
        $this->settings = $settings;
        $this->view = $view;
        $this->flash = $flash;
    }

    /**
     * Make an API call.
     *
     * @param string $method Resource method.
     * @param string $uri Resource URI. This is the string after <domain>/<account>/<application>/.
     * @param array $requestOptions Request optionsL query params, header, etc.
     *
     * @return ResponseInterface
     *
     * @throws Exception API call returned an exception, unify into a single Exception type.
     */
    public function apiCall(string $method, string $uri, array $requestOptions = []): ResponseInterface
    {
        try {
            $requestOptions['protocols'] = $this->settings['admin']['protocols'];
            $domain = $this->settings['admin']['api_url'];
            $account = $this->settings['admin']['core_account'];
            $application = $this->settings['admin']['core_application'];
            $client = new Client(['base_uri' => "$domain/$account/$application/"]);
            return $client->request($method, $uri, $requestOptions);
        } catch (BadResponseException $e) {
            $result = $e->getResponse();
            switch ($result->getStatusCode()) {
                case 401:
                    throw new Exception('Unauthorised');
                default:
                    throw new Exception($this->getErrorMessage($e));
            }
        } catch (GuzzleException $e) {
            $result = $e->getResponse();
            switch ($result->getStatusCode()) {
                case 401:
                    throw new Exception('Unauthorised');
                default:
                    throw new Exception($this->getErrorMessage($e));
            }
        }
    }

    /**
     * Fetch all user roles for a user.
     *
     * @param integer $uid User ID.
     *
     * @return array
     */
    protected function apiCallUserRoles(int $uid): array
    {
        $result = [];
        try {
            $result = $this->apiCall('GET', 'user/role', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => ['uid' => $uid],
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }
        return $result;
    }

    /**
     * Fetch all roles.
     *
     * @return array
     */
    protected function apiCallRolesAll(): array
    {
        $userRoles = [];
        try {
            $result = $this->apiCall('GET', 'role', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
            foreach ($result as $role) {
                $userRoles[$role['rid']] = $role['name'];
            }
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }
        return $userRoles;
    }

    /**
     * Fetch all Accounts.
     *
     * @param array $params Sort params.
     *
     * @return array
     */
    protected function apiCallAccountAll(array $params = []): array
    {
        $result = $query = [];
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }
        $query['order_by'] = empty($query['order_by']) ? 'name' : $query['order_by'];

        try {
            $result = $this->apiCall('GET', 'account', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $query,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
        } catch (Exception $e) {
            $this->flash->addMessageNow('data', $e->getMessage());
        }

        return $result;
    }

    /**
     * Fetch all applications from the API.
     *
     * @param array $params Sort params.
     *
     * @return array
     */
    protected function apiCallApplicationAll(array $params = []): array
    {
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }
        $query['order_by'] = empty($query['order_by']) ? 'name' : $query['order_by'];

        $result = [];
        try {
            $result = $this->apiCall('GET', 'application', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $query,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $result;
    }

    /**
     * Fetch the access rights for a user.
     *
     * @param int|null $uid User ID.
     *
     * @return array user access rights.
     *   [
     *     <accid>> => [
     *       <appid> => [
     *         <rid>,
     *       ],
     *     ],
     *   ]
     */
    private function getAccessRights(int $uid = null): array
    {
        if (empty($uid)) {
            $uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : '';
        }
        $userRoles = $this->apiCallUserRoles($uid);

        $userAccessRights = [];

        foreach ($userRoles as $userRole) {
            $accid = $userRole['accid'] == null ? 0 : $userRole['accid'];
            $appid = $userRole['appid'] == null ? 0 : $userRole['appid'];
            $userAccessRights[$accid][$appid][] = $userRole['rid'];
        }

        return $userAccessRights;
    }

    /**
     * Get all roles for user.
     *
     * @return array
     *   [<rid> => <rolename>]
     */
    private function getRoles(): array
    {
        $roles = [];

        foreach ($this->userAccessRights as $appids) {
            foreach ($appids as $rids) {
                foreach ($rids as $rid) {
                    $roles[$rid] = $this->allRoles[$rid];
                }
            }
        }

        return $roles;
    }

    /**
     * Get accounts for the user.
     *
     * @param array $params Sort and filter params.
     *
     * @return array Array of account names that the user has permissions for.
     * Example:
     *   [<accid> => <account_name>]
     */
    private function getAccounts(array $params = []): array
    {
        $result = $query = [];
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        try {
            $result = $this->apiCall('GET', 'account', [
                'headers' => [
                    'Authorization' => "Bearer " . $_SESSION['token'],
                    'Accept' => 'application/json',
                ],
                'query' => $query,
            ]);
            $result = json_decode($result->getBody()->getContents(), true);
            if (isset($result['result']) && $result['result'] == 'ok' && isset($result['data'])) {
                $result = $result['data'];
            }
        } catch (Exception $e) {
            $this->flash->addMessageNow('error', $e->getMessage());
        }

        return $result;
    }

    /**
     * Get applications for the user.
     *
     * @param array $params Sort and filter params.
     *
     * @return array Array of applications the user has rights to.
     * Example:
     *     [
     *       appid => [
     *         'name' => <app_name>,
     *         'accid' => <accid>,
     *       ],
     *     ]
     */
    protected function getApplications(array $params = []): array
    {
        $allApplications = $this->apiCallApplicationAll($params);

        if (isset($this->userAccessRights[0])) {
            // User has access to all accounts, so all applications.
            return $allApplications;
        }

        $applications = [];
        foreach ($this->userAccessRights as $accid => $apps) {
            if (isset($apps[0])) {
                // User has access to all applications in the account.
                foreach ($allApplications as $appid => $application) {
                    if ($accid == $application['accid']) {
                        $applications[$appid] = $application;
                    }
                }
            } else {
                foreach ($apps as $appid => $rids) {
                    $applications[$appid] = $allApplications[$appid];
                }
            }
        }

        return $applications;
    }

    /**
     * Validate user access by role.
     *
     * @return boolean Access validated.
     */
    protected function checkAccess(): bool
    {
        if (empty($this->userAccessRights) || empty($this->allRoles)) {
            $this->allRoles = $this->apiCallRolesAll();
            $this->userAccessRights = $this->getAccessRights();
            $this->userAccounts = $this->getAccounts();
            $this->userApplications = $this->getApplications();
            $this->userRoles = $this->getRoles();
        }
        if (empty($this->permittedRoles)) {
            return true;
        }

        foreach ($this->userRoles as $name) {
            if (in_array($name, $this->permittedRoles)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get available menu items for user's roles.
     *
     * @return array Associative array of menu titles and links.
     */
    protected function getMenus(): array
    {
        $menus = [];

        if (empty($_SESSION['uid'])) {
            $menus += [
                'Login' => '/login',
            ];
        } else {
            $menus += [
                'Home' => '/',
                'Open Api' => '/open-api',
            ];
            if (in_array('Administrator', $this->userRoles)) {
                $menus += [
                    'Accounts' => '/accounts',
                    'Applications' => '/applications',
                    'Users' => '/users',
                    'Invites' => '/invites',
                    'User Roles' => '/user/roles',
                    'Roles' => '/roles',
                ];
            }
            if (in_array('Account manager', $this->userRoles)) {
                $menus += [
                    'Accounts' => '/accounts',
                    'Applications' => '/applications',
                    'Users' => '/users',
                    'Invites' => '/invites',
                    'User Roles' => '/user/roles',
                    'Roles' => '/roles',
                ];
            }
            if (in_array('Application manager', $this->userRoles)) {
                $menus += [
                    'Accounts' => '/accounts',
                    'Applications' => '/applications',
                    'Users' => '/users',
                    'Invites' => '/invites',
                    'User Roles' => '/user/roles',
                ];
            }
            if (in_array('Developer', $this->userRoles)) {
                $menus += [
                    'Accounts' => '/accounts',
                    'Applications' => '/applications',
                    'Resources' => '/resources',
                    'Vars' => '/vars',
                ];
                $menus['Open Api'] = [
                    'Docs' => '/open-api',
                    'Editor' => '/open-api/edit',
                    'Import' => '/open-api/import',
                ];
            }
            $uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : '';
            $menus += [
                'My account' => "/user/view/$uid",
                'Logout' => '/logout',
            ];
        }

        return $menus;
    }

    /**
     * Get an error message from a API call exception.
     *
     * @param mixed $e Exception.
     *
     * @return string
     */
    protected function getErrorMessage($e): string
    {
        $message = '';
        if ($e->hasResponse()) {
            $responseObject = json_decode($e->getResponse()->getBody()->getContents());
            $message = $responseObject->data->message;
        }
        if (empty($message)) {
            $message = $e->getMessage();
        }
        return $message;
    }
}
