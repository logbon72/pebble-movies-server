<?php

/**
 * Description of Utilities
 *
 * @author intelWorX
 */
class Utilities extends IdeoObject {

    //put your code here

    public static function getFolderUrl($folder, $relative = false) {
        if (strpos($folder, APP_ROOT) === false) {
            return '';
        }

        return str_replace(array(APP_ROOT, '\\'), array($relative ? '' : BASE_URL, '/'), $folder);
    }

    public static function getRandomCode($length = 15) {
        return substr(base64_encode(hash('sha256',uniqid('random_code'))), 0, $length);
    }

    public static function getFileExtension($filename) {
        $tmp = explode('.', $filename);
        return strtolower(end($tmp));
    }

    public static function capitalize($str, $lc_rest = true) {
        TemplateEngine::getInstance()->loadPlugin('smarty_modifier_capitalize');
        return smarty_modifier_capitalize($str, false, $lc_rest);
    }

    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function getCustomUrl($tag, $secure = false) {
        return ($secure ? "https://" : "http://") . ($tag ? "{$tag}." : "") . SystemConfig::getInstance()->system['root_domain'] . "/";
    }
    
    public static function dateFromOffset($date, $offset=0, $format="Y-m-d") {
        $dateTs = strtotime($date);
        $newTs = $offset > 0 ? strtotime("+{$offset} days", $dateTs) : $dateTs;
        return date($format,  $newTs);
    }

}

