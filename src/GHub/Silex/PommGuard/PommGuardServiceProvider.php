<?php

namespace GHub\Silex\PommGuard;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class PommGuardServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!$app->offsetExists('pomm_guard.config.login_url')) {
            $app['pomm_guard.config.login_url'] = '/login';
        }

        if (!$app->offsetExists('pomm_guard.config.logout_url')) {
            $app['pomm_guard.config.logout_url'] = '/logout';
        }

        if ($app->offsetExists('pomm_guard.config.connection')) {
            if (!(is_object($app['pomm_guard.config.connection']) 
                and $app['pomm_guard.config.connection'] instanceof \Pomm\Connection\Connection)) {

                    throw new \InvalidArgumentException(
                        sprintf("'pomm_guard.config.connection' should be an instance of '\\Pomm\\Connection\\Connection'."));
                }
        } else {
            $app['pomm_guard.config.connection'] = $app['pomm']->getDatabase()->createConnection();
        }

        $app['pomm_guard.must_be_authenticated'] = $app->protect(function(Request $request) use ($app) {
            if (!$app['session']->isAuthenticated()) 
            {
                return $app->redirect($app['pomm_guard.config.login_url']);
            }
        });

        $app['pomm_guard.must_not_be_authenticated'] = $app->protect(function(Request $request) use ($app) {
            if ($app['session']->isAuthenticated()) 
            {
                return $app->redirect($app['pomm_guard.config.logout_url']);
            }
        });

        $app['session'] = $app->share(function () use ($app) {
            return new Session($app['session.storage']);
        });

        $app['session']->setUserMap($app['pomm_guard.config.connection']
            ->getMapFor($app['pomm_guard.config.user']));
    }
}



