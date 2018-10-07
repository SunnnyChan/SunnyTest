<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file AutoLoader.php
 * @date 2015/06/26
 * @author sunnnychan@gmail.com
 * @brief 
 *
 **/

class AutoLoader {
    private static $arrMap = null;

    public static function addClassMap($arrMap = array()){
        if (! $arrMap){
            self::$arrMap = $arrMap;
            spl_autoload_register(array('Autoloader', 'loadMain'));
            spl_autoload_register(array('Autoloader', 'loadTestmodConf'));
            spl_autoload_register(array('Autoloader', 'loadMockConf'));
        } else {
            self::$arrMap = array_merge(self::$arrMap, $arrMap);
        }
    }

    public static function reset(){
        spl_autoload_unregister(array('Autoloader', 'autoLoad'));
        self::$arrMap = null;
    }

    public static function loadMain($className){
        if (isset(self::$arrMap[$className])){
            $file = self::$arrMap[$className];
            if($file{0} == '/'){
                require_once $file;
            } else {
                require_once MAIN_PATH . '/' . $file;
            }
        } else {
            $str = str_replace('_', '/', $className);
            $file = MAIN_PATH . '/' . strtolower(dirname($str)) . '/' . basename($str) . '.php';
            if (file_exists($file)){
                require_once($file);
            }
        }
    }   

    public static function loadTestmodConf($className){
        $confFile = CONF_PATH . '/testmod/' . $className . '.conf.php';
        if (file_exists($confFile)){
            require_once($confFile);
        } 
    }

    public static function loadMockConf($className){
        $confFile = CONF_PATH . '/' . $className . '.php';
        if (file_exists($confFile)){
            require_once($confFile);
        } 
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
