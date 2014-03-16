<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entitymanagers;

/**
 * Description of AppEntityManager
 *
 * @author intelWorX
 */
abstract class AppEntityManager extends \EntityManager {

    const ENTITY_NS = '\models\entities';
    const DBTABLE_NS = '\models\dbtables';

    public function __construct($entityName = null) {
        if (!$entityName) {
            $managerName = static::getClassBasic();
            $entityName = preg_replace('/Manager$/', '', $managerName);
        }
        
        $entityTableClass = static::DBTABLE_NS . '\\' . $entityName . 'Table';
        $entityClass = static::ENTITY_NS . '\\' . $entityName;

        if (!class_exists($entityTableClass)) {
            if (class_exists($entityClass)) {
                $entityTableClass = new \EntityTable($entityClass);
            } else {
                throw new \Exception("Auto detected Table class {$entityTableClass} was not found, consider explicit declaration, additionally, an entity class was not found.");
            }
        }

        if (!class_exists($entityClass)) {
            $entityClass = \models\entities\StandardEntity::getClass();
        }

        parent::__construct($entityTableClass, $entityClass);
    }

}
