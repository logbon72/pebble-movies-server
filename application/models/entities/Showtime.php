<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of Showtime
 *
 * @author intelWorX
 */
class Showtime extends StandardEntity
{

    const TYPE_2D = 'digital';
    const TYPE_3D = 'digital 3D';
    const TYPE_IMAX = 'IMAX';
    //put your code here
    protected static $COMPACT = array(
        self::TYPE_2D => 'd',
        self::TYPE_3D => '3d',
        self::TYPE_IMAX => 'i',
    );

    public static function compact($type)
    {
        return self::$COMPACT[$type];
    }

    protected function initRelations()
    {
        $this->setManyToOne('theatre', Theatre::manager())
            ->setManyToOne('movie', Movie::manager());
    }


}
