<?php

namespace GHub\Silex\PommGuard\Model;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class PommUserMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'GHub\Silex\PommGuard\Model\PommUser';
        $this->object_name  =  'pomm_guard.pomm_user';

        $this->addField('login', 'varchar');
        $this->addField('password', 'varchar');
        $this->addField('groups', 'varchar[]');

        $this->pk_fields = array('login');
    }
}
