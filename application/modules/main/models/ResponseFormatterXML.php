<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace main\models;

/**
 * Description of ResponseFormatterXML
 *
 * @author intelWorX
 */
class ResponseFormatterXML extends ResponseFormatter {

    public function format(array $result, $pretty = false) {
        //debug_op($result, true);
        $xml = ArrayToXML::toXML($result, Response::XML_ROOT);
        if ($pretty) {
            $doc = new \DOMDocument('1.0');
            $doc->preserveWhiteSpace = false;
            $doc->loadXML($xml);
            $doc->formatOutput = true;
            return $doc->saveXML();
        } else {
            return $xml;
        }
    }

}
