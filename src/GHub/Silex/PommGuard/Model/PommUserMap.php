<?php

namespace GHub\Silex\PommGuard\Model;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;
use \Pomm\Query\Where;

class PommUserMap extends BaseObjectMap
{
    protected $group_map;

    public function initialize()
    {

        $this->object_class =  'GHub\Silex\PommGuard\Model\PommUser';
        $this->object_name  =  'pomm_guard.pomm_user';

        $this->addField('login', 'varchar');
        $this->addField('password', 'varchar');
        $this->addField('groups', 'varchar[]');

        $this->pk_fields = array('login');
        $this->group_map = $this->connection
            ->getMapFor('GHub\Silex\PommGuard\Model\PommGroup');
    }

    public function checkPassword(Array $pk, $password, $plain = false)
    {
        $where = new Where();
        foreach ($pk as $field => $value)
        {
            $where->andWhere(sprintf('%s = ?', $field), array($value));
        }

        if ($plain === false)
        {
            $where->andWhere('crypt(?, password) = password', array($password));
        }
        else
        {
            $where->andWhere('password = ?', array($password));
        }

        $results = $this->findWhere($where, $where->getValues());

        return (!$results->isEmpty()) ? $results->current() : null;
    }

    public function findByPkWithAcls(Array $pk)
    {
        $where = new Where();
        foreach ($pk as $field => $value)
        {
            $where->andWhere(sprintf("%s = ?", $field), array($value));
        }

        $sql = <<<_
WITH
  raw_acls (name) AS (
    SELECT DISTINCT 
        unnest(credentials) 
    FROM 
        %s v, 
        %s g
    WHERE 
        g.name = ANY(v.groups) 
      AND 
        %s
    ),
  agg_acls (acls) AS (
    SELECT
       CASE
         WHEN count(name) = 0 THEN ARRAY[]::varchar[]
         ELSE array_agg(name)
       END AS acls
    FROM
      raw_acls
    )
SELECT 
    %s,
    a.acls AS credentials 
FROM 
    %s v, 
    agg_acls a 
WHERE 
    %s
_;

        $sql = sprintf($sql,
            $this->getTableName(),
            $this->group_map->getTableName(),
            (string) $where,
            join(', ', $this->getSelectFields('v')),
            $this->getTableName(),
            (string) $where
        );

        $this->addVirtualField('credentials', 'varchar[]');
        $pomm_users = $this->query($sql, array_merge($where->getValues(), $where->getValues()));

        return (!$pomm_users->isEmpty()) ? $pomm_users->current() : null;
    }

    public function getSelectFields($alias = null) 
    {
        return array_filter($this->getFields($alias), function($val) {
            if (!preg_match('/password$/', $val)) 
            {
                return $val;
            }
        });
    }
}
