<?php

/**
 * Class CtrlHome.
 *
 * Controller for the home page.
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
 * Class CtrlHome.
 *
 * Controller for the home page.
 */
class CtrlHome extends CtrlBase
{
    /**
     * Roles allowed to visit the page.
     *
     * @var array
     */
    protected array $permittedRoles = [];

    /**
     * Home page.
     *
     * @param \Slim\Http\Request $request Request object.
     * @param \Slim\Http\Response $response Response object.
     * @param array $args Request args.
     *
     * @return ResponseInterface
     */
    public function index(Request $request, Response $response, array $args): ResponseInterface
    {
        // Validate access.
        if (!$this->checkAccess()) {
            $this->flash->addMessage('error', 'Access denied');
            return $response->withStatus(302)->withHeader('Location', '/logout');
        }

        $menu = $this->getMenus();

        return $this->view->render($response, 'home.twig', [
            'menu' => $menu,
            'accounts' => $this->userAccounts,
            'applications' => $this->userApplications,
            'roles' => $this->userRoles,
            'flash' => $this->flash,
        ]);
    }
}
