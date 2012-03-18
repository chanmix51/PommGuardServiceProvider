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

        $app['pomm_guard.config.database'] = 
            $app->offsetExists('pomm_guard.config.database') 
            ? $app['pomm_guard.config.database']
            : null;

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

        $app['session']->setUserMap($app['pomm']
            ->getDatabase($app['pomm_guard.config.database'])
            ->createConnection()
            ->getMapFor($app['pomm_guard.config.user']));
    }
}



