<?php
/**
 * Created by PhpStorm.
 * User: intelWorx
 * Date: 16/12/2015
 * Time: 6:19 PM
 */



class Bootstrap extends \ApplicationBootstrap
{

    const ENTITY_NS = '\models\entities';
    const ENTITY_MANAGER_NS = '\models\entitymanagers';
    const DBTABLE_NS = '\models\dbtables';

    protected function _initEntityManager()
    {
        \GenericEntityManager::configure(self::ENTITY_NS, self::DBTABLE_NS, self::ENTITY_MANAGER_NS);
    }
}