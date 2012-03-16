<?php

namespace GHub\Silex\PommGuard\Model;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class PommGroupMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'GHub\Silex\PommGuard\Model\PommGroup';
        $this->object_name  =  'pomm_guard.pomm_group';

        $this->addField('name', 'varchar');
        $this->addField('credentials', 'varchar[]');

        $this->pk_fields = array('name');
    }
}
