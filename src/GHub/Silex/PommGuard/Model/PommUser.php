<?php

namespace GHub\Silex\PommGuard\Model;

use \Pomm\Object\BaseObject;
use \Pomm\Exception\Exception;

class PommUser extends BaseObject
{
    public function hasCredential($credential)
    {
        if ($this->has('credentials')) 
        {
            return array_key_exists($this->getCredentials(), $credential);
        }

        throw new Exception('This object does not have credential info. Check your queries.');
    }

    public function hasCredentials(Array $credentials)
    {
        if ($this->has('credentials')) 
        {
            foreach ($credentials as $credential)
            {
                if (!array_key_exists($this->getCredentials(), $credential))
                {
                    return false;
                }
            }

            return true;
        }

        throw new Exception('This object does not have credential info. Check your queries.');
    }
}

