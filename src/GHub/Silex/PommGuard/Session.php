<?php

/**
 * Session
 *
 * This file is part of the PommGuard project.
 * Copyleft 2012 Grégoire HUBERT <hubert.greg@gmail.com>
 * This is free software please check the LICENCE.txt file that comes with this 
 * package.
 **/

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

    /**
     * __construct
     *
     * @see \Symfony\Component\HttpFoundation\Session\Session
     *
     * @param \Pomm\Object\BaseObjectMap $user_map The map class that manages users.
     * @param \Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface $storage
     * @param \Symfony\Component\HttpFoundation\Session\Storage\AttributeBagInterface   $attributes
     * @param \Symfony\Component\HttpFoundation\Session\Storage\FlashBagInterface       $flashes
     **/
    public function __construct(BaseObjectMap $user_map, SessionStorageInterface $storage = null, AttributeBagInterface $attributes = null, FlashBagInterface $flashes = null)
    {
        parent::__construct($storage, $attributes, $flashes);
        $this->user_map = $user_map;
    }

    /**
     * setPommUser
     *
     * Tie a user to the current session.
     * 
     * @param \Model\PommUser $user
     **/
    public function setPommUser(Model\PommUser $user)
    {
        $this->set('_pg_guard_user', $user->get($this->user_map->getPrimaryKey()));
    }

    /**
     * removePommUser
     *
     * Anonymize the session.
     **/
    public function removePommUser()
    {
        $this->remove('_pg_guard_user');
    }

    /**
     * getPommUser
     *
     * Return the current session's user if any, null otherwise.
     *
     * @return \Model\PommUser
     **/
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

    /**
     * authenticate
     *
     * Mark the session as authenticated or not.
     *
     * @param boolean $authenticate
     **/
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

    /**
     * isAuthenticated
     *
     * Return the authentication state.
     *
     * @return boolean
     **/
    public function isAuthenticated()
    {
        return $this->has('_pg_is_authenticated');
    }

    /**
     * hasCredential
     *
     * Return wether or not the session owns given credential.
     *
     * @param  string $credential
     * @return boolean
     **/
    public function hasCredential($credential)
    {
        $user = $this->getPommUser();

        return (!is_null($user)) ? ($user->hasCredential($credential)) : false;
    }

    /**
     * hasCredentials
     *
     * Return wether or not the session owns given credentials.
     *
     * @param  Array $credentials
     * @return boolean 
     **/
    public function hasCredentials(Array $credentials)
    {
        $user = $this->getPommUser();

        return (!is_null($user)) ? ($user->hasCredentials($credentials)) : false;
    }

}
