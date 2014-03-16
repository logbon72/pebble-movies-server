<?php

/**
 * http://api.geonames.org/findNearbyJSON?lat=6.54839&lng=3.3841109&username=demo
 * {"geonames":[{"countryId":"2328926","adminCode1":"05","countryName":"Nigeria","fclName":"country, state, region,...","countryCode":"NG","lng":"3.3807","fcodeName":"second-order administrative division","distance":"1.07799","toponymName":"Shomolu","fcl":"A","name":"Shomolu","fcode":"ADM2","geonameId":7871280,"lat":"6.55752","adminName1":"Lagos","population":0}]}
 * 
 * http://api.geonames.org/findNearbyPostalCodesJSON?lat=47&lng=9&username=demo
 * {"postalCodes":[{"adminCode3":"1631","adminName2":"Glarus","adminName3":"Glarus Süd","adminCode2":"800","distance":"2.21233","adminCode1":"GL","postalCode":"8775","countryCode":"CH","lng":8.998612768346122,"placeName":"Luchsingen","lat":46.98012557612474,"adminName1":"Kanton Glarus"},{"adminCode3":"1632","adminName2":"Glarus","adminName3":"Glarus","adminCode2":"800","distance":"2.85264","adminCode1":"GL","postalCode":"8750","countryCode":"CH","lng":9.002485443433116,"placeName":"Riedern","lat":47.025599625015275,"adminName1":"Kanton Glarus"},{"adminCode3":"1631","adminName2":"Glarus","adminName3":"Glarus Süd","adminCode2":"800","distance":"3.30591","adminCode1":"GL","postalCode":"8774","countryCode":"CH","lng":9.031657981449875,"placeName":"Leuggelbach","lat":46.97956309637888,"adminName1":"Kanton Glarus"},{"adminCode3":"1631","adminName2":"Glarus","adminName3":"Glarus Süd","adminCode2":"800","distance":"3.71448","adminCode1":"GL","postalCode":"8772","countryCode":"CH","lng":9.045686144787961,"placeName":"Nidfurn","lat":46.98795940542871,"adminName1":"Kanton Glarus"},{"adminCode3":"1632","adminName2":"Glarus","adminName3":"Glarus","adminCode2":"800","distance":"4.04547","adminCode1":"GL","postalCode":"8750","countryCode":"CH","lng":8.947224392680244,"placeName":"Klöntal","lat":47.00532963127739,"adminName1":"Kanton Glarus"}]}
 */

namespace models\services\locationproviders;
/**
 * Description of GeoNames
 *
 * @author intelWorX
 */
class GeoNames extends \models\services\LocationServiceProvider{
    
    public function lookUp($long, $lat) {
        throw new Exception("Not implemented");
    }

}
