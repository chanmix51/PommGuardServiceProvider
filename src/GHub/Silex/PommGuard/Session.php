<?php

namespace GHub\Silex\PommGuard;

use Symfony\Component\HttpFoundation\Session\Session AS SfSession;
use GHub\Silex\PommGuard\Model\PommGuard\PommGuardUser;
use Pomm\Object\BaseObjectMap;

class Session extends SfSession
{
    protected $pomm_guard_user;
    protected $user_map;

    public function setUserMap(BaseObjectMap $instance)
    {
        $this->user_map = $instance;
    }

    public function setPommGuardUser(PommGuardUser $user)
    {
        $this->pomm_guard_user = $user;

        foreach ($this->user_map->getPrimaryKey() as $key)
        {
            $this->set(sprintf('_pg_%s', $key), $user[$key]);
        }
    }

    public function removePommGuardUser()
    {
        foreach ($this->user_map->getPrimaryKey() as $key)
        {
            $this->remove(sprintf('_pg_%s', $key));
        }
    }

    public function getPommGuardUser()
    {
        if (is_null($this->pomm_guard_user)) 
        {
            $pk = array();
            foreach ($this->user_map->getPrimaryKey() as $key)
            {
                $pk[$key] = $this->get(sprintf('_pg_%s', $key));
            }

            $this->pomm_guard_user = $this->user_map->findByPkWithAcls($pk);
        }

        return $this->pomm_guard_user;
    }

    public function authenticate($authenticate)
    {
        if ($authenticate === true) 
        {
            $this->set('_pg_is_authenticated', true);
        }
        elseif ($this->has('_pg_is_authenticated')) 
        {
            $this->remove('_pg_is_authenticated');
        }
    }

    public function isAuthenticated()
    {
        return $this->has('_pg_is_authenticated');
    }

    public function hasCredential($credential)
    {
        $user = $this->getPommGuardUser();

        return (!is_null($user)) ? ($user->hasCredential($credential)) : false;
    }


    public function hasCredentials(Array $credentials)
    {
        $user = $this->getPommGuardUser();

        return (!is_null($user)) ? ($user->hasCredentials($credentials)) : false;
    }

}
