<?php

namespace GHub\Silex\PommGuard;

use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class PommGuardServiceProvider extends SessionServiceProvider 
{
    public function register(Application $app)
    {
        parent::register($app);

        if (!$app->offsetExists('pomm_guard.config.login_url')) 
        {
            $app['pomm_guard.config.login_url'] = '/login';
        }

        if (!$app->offsetExists('pomm_guard.config.logout_url')) 
        {
            $app['pomm_guard.config.logout_url'] = '/logout';
        }

        if (!$app->offsetExists('pomm_guard.config.user')) 
        {
            $app['pomm_guard.config.user'] = '\GHub\Silex\PommGuard\Model\PommUser';
        }

        if (!$app->offsetExists('pomm_guard.config.connection')) 
        {
            $app['pomm_guard.config.connection'] = $app->share(function() use ($app) {
                return $app['pomm']->getDatabase()->createConnection();
            });
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
            return new Session($app['pomm_guard.config.connection']->getMapFor($app['pomm_guard.config.user']), $app['session.storage']);
        });
    }
}



