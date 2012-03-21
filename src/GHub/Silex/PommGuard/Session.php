<?php

namespace GHub\Silex\PommGuard;

use Symfony\Component\HttpFoundation\Session\Session AS SfSession;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use GHub\Silex\PommGuard\Model;

use Pomm\Object\BaseObjectMap;

class Session extends SfSession
{
    protected $pomm_guard_user;
    protected $user_map;

    public function __construct(BaseObjectMap $user_map, SessionStorageInterface $storage = null, AttributeBagInterface $attributes = null, FlashBagInterface $flashes = null)
    {
        parent::__construct($storage, $attributes, $flashes);
        $this->user_map = $user_map;
    }

    public function setPommUser(Model\PommUser $user)
    {
        $this->set('_pg_guard_user', $user->get($this->user_map->getPrimaryKey()));
    }

    public function removePommUser()
    {
        $this->remove('_pg_guard_user');
    }

    public function getPommUser()
    {
        if (!$this->has('_pg_guard_user')) 
        {
            return null;
        }

        if (is_null($this->pomm_guard_user)) 
        {
            $this->pomm_guard_user = $this->user_map->findByPkWithAcls($this->get('_pg_guard_user'));
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
        $user = $this->getPommUser();

        return (!is_null($user)) ? ($user->hasCredential($credential)) : false;
    }

    public function hasCredentials(Array $credentials)
    {
        $user = $this->getPommUser();

        return (!is_null($user)) ? ($user->hasCredentials($credentials)) : false;
    }

}
