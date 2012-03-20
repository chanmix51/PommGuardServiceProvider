<?php


namespace \GHub\Silex\PommGuard\Test;

use Silex\Application;
use GHub\Silex\PommGuard\PommGuardServiceProvider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Session test cases.
 *
 * @Grégoire Hubert <hubert.greg@gmail.com>
 */
class PommGuardServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->register(new PommGuardServiceProvider(), array(
            
));

        $app['session.storage'] = $app->share(function () use ($app) {
            return new MockArraySessionStorage();
        });

        $app->get('/login', function () use ($app) {
            $app['session']->set('logged_in', true);
            return 'Logged in successfully.';
        });

        $app->get('/account', function () use ($app) {
            if (!$app['session']->get('logged_in')) {
                return 'You are not logged in.';
            }

            return 'This is your account.';
        });

        $request = Request::create('/login');
        $response = $app->handle($request);
        $this->assertEquals('Logged in successfully.', $response->getContent());

        $request = Request::create('/account');
        $response = $app->handle($request);
        $this->assertEquals('This is your account.', $response->getContent());
    }
}
