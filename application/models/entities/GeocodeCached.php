<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace models\entities;

/**
 * Description of GeocodeCached
 *
 * @author intelWorX
 */
class GeocodeCached extends StandardEntity {

    public function getQueryWhere() {
        $queryWhere = new \DbTableWhere();
        $queryWhere->where('country_iso', $this->_data['country_iso']);
        if ($this->_data['postal_code']) {
            $queryWhere->where('postal_code', $this->_data['postal_code']);
        } else {
            $queryWhere->where('city', $this->_data['city']);
        }
        return $queryWhere;
    }

    public function getGeocode() {
        $address = $this->_data['postal_code'] .' '.$this->_data['city'] . ', ' . $this->_data['country_iso'];
        return new \models\GeoLocation($this->_data['found_latitude'], $this->_data['found_longitude'], $address);
    }
}
