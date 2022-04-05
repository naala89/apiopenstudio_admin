<?php

/**
 * Class Authentication.
 *
 * @package    ApiOpenStudioAdmin
 * @subpackage Middleware
 * @author     john89 (https://gitlab.com/john89)
 * @copyright  2020-2030 Naala Pty Ltd
 * @license    This Source Code Form is subject to the terms of the ApiOpenStudio Public License.
 *             If a copy of the MPL was not distributed with this file,
 *             You can obtain one at https://www.apiopenstudio.com/license/.
 * @link       https://www.apiopenstudio.com
 */

namespace ApiOpenStudioAdmin\Middleware;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Authentication.
 *
 * A Slim PHP middle class to validate a user's logged in status and redirect if unauthenticated.
 */
class Authentication
{
    /**
     * Settings container.
     *
     * @var Container
     */
    private $settings;

    /**
     * Login URI.
     *
     * @var string
     */
    private string $loginPath;

    /**
     * Middleware container.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Authentication constructor.
     *
     * @param Container $container Container.
     * @param array $settings Application settings.
     * @param string $loginPath Login URI.
     */
    public function __construct(Container $container, array $settings, string $loginPath)
    {
        $this->container = $container;
        $this->settings = $settings;
        $this->loginPath = $loginPath;
    }

    /**
     * Middleware invocation.
     *
     * @param ServerRequestInterface $request PSR7 request.
     * @param ResponseInterface $response PSR7 Response.
     * @param callable $next Next middleware.
     *
     * @return ResponseInterface Response Interface.
     * @throws Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $domain = $this->settings['admin']['api_url'];
        $account = $this->settings['admin']['core_account'];
        $application = $this->settings['admin']['core_application'];
        $client = new Client(['base_uri' => "$domain/$account/$application/"]);

        if (!empty($username) || !empty($password)) {
            // This is a login attempt.
            try {
                $result = $client->request('POST', "auth/token", [
                    'form_params' => [
                        'username' => $username,
                        'password' => $password,
                    ]
                ]);
                $result = json_decode($result->getBody()->getContents(), true);
                if (isset($result['result']) && isset($result['data'])) {
                    $result = $result['data'];
                }
                if (!isset($result['token']) || !isset($result['uid'])) {
                    return $response->withStatus(302)->withHeader('Location', '/login');
                }
                $_SESSION['token'] = $result['token'];
                $_SESSION['uid'] = $result['uid'];
                $_SESSION['username'] = $username;
            } catch (BadResponseException | GuzzleException $e) {
                $json = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->container['flash']->addMessage('error', $json['data']['message']);
            }
        } else {
            // Validate the token and username.
            try {
                $token = $_SESSION['token'] ?? '';
                $username = $_SESSION['username'] ?? '';
                $client->request('GET', "user", [
                    'headers' => ['Authorization' => "Bearer " . $token],
                    'query' => ['username' => $username],
                ]);
            } catch (BadResponseException | RequestException $e) {
                $this->container['flash']->addMessage('error', $e->getMessage());
                unset($_SESSION['token']);
                unset($_SESSION['username']);
                unset($_SESSION['uid']);
            } catch (GuzzleException $e) {
                $this->container['flash']->addMessageNow('error', 'Internal server error');
                unset($_SESSION['token']);
                unset($_SESSION['username']);
                unset($_SESSION['uid']);
            }
        }

        // Validate token and uid are set (valid login).
        if (!isset($_SESSION['token']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
            $loginPath = $request->getUri()->withPath($this->loginPath);
            return $response->withStatus(302)->withHeader('Location', $loginPath);
        }
        return $next($request, $response);
    }
}
