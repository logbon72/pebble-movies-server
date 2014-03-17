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
class Showtime extends StandardEntity {

    //put your code here

    protected function initRelations() {
        $this->setManyToOne('theatre', Theatre::manager())
                ->setManyToOne('movie', Movie::manager());
    }
   

}
