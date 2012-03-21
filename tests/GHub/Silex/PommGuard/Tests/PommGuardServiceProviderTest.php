<?php

namespace GHub\Silex\PommGuard\Tests;

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use GHub\Silex\PommGuard\PommGuardServiceProvider;
use GHub\Silex\Pomm\PommServiceProvider;

/**
 * PommGuardServiceProvider test cases.
 *
 * @Grégoire Hubert <hubert.greg@gmail.com>
 */
class PommGuardServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();

        $app->register(new PommServiceProvider(), array(
            'pomm.databases' => array('default' => array('dsn' => 'pgsql://test/test', 'name' => 'test'))
        ));

        $app->register(new PommGuardServiceProvider());

        $app['session.storage'] = $app->share(function () use ($app) {
            return new MockArraySessionStorage();
        });

        $app->get('/login', function() use ($app) {
            return 'Login page';
        });

        $app->get('/logout', function() use ($app) {
            $app['session']->authenticate(false);

            return 'Logged out successfully.';
        });

        $app->get('/protected', function() use ($app) {
            return 'Protected page.';
        })->middleware($app['pomm_guard.must_be_authenticated']);

        $app->get('/not-protected', function() use ($app) {
            return 'Not protected page.';
        })->middleware($app['pomm_guard.must_not_be_authenticated']);

        $app->get('/authenticate', function () use ($app) {
            $app['session']->authenticate(true);

            return 'Logged in successfully.';
        });

        $request = Request::create('/protected');
        $response = $app->handle($request);
        $this->assertTrue($response->isRedirect('/login'));

        $request = Request::create('/not-protected');
        $response = $app->handle($request);
        $this->assertEquals('Not protected page.', $response->getContent());

        $request = Request::create('/authenticate');
        $response = $app->handle($request);
        $this->assertEquals('Logged in successfully.', $response->getContent());

        $request = Request::create('/protected');
        $response = $app->handle($request);
        $this->assertEquals('Protected page.', $response->getContent());

        $request = Request::create('/not-protected');
        $response = $app->handle($request);
        $this->assertTrue($response->isRedirect('/logout'));

        $request = Request::create('/logout');
        $response = $app->handle($request);
        $this->assertEquals('Logged out successfully.', $response->getContent());

    }
}
