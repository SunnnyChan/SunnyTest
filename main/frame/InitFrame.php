<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file InitFrame.php
 * @date 2015/06/29
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class InitFrame {
    private static $initFlag = false;

    public static function init(){
        if (self::$initFlag) {
            return false;
        }
        self::$initFlag = true;

        self::defEnvDir();

        //自动加载类
        AutoLoader::addClassMap();
        $libClassMap = array(
            'Http' => 'lib/http/Http.php',
            'HttpHeader' => 'lib/http/HttpHeader.php',
            'HttpRequestHeader' => 'lib/http/HttpRequestHeader.php',
            'HttpRequestMessage' => 'lib/http/HttpRequestMessage.php',
            'HttpResponseHeader' => 'lib/http/HttpResponseHeader.php',
            'HttpResponseMessage' => 'lib/http/HttpResponseMessage.php',
            'HttpDef' => 'lib/http/HttpDef.php',
            'HttpCookie' => 'lib/http/HttpCookie',
        );
        AutoLoader::addClassMap($libClassMap);

        set_time_limit(10);
    }

    private static function defEnvDir(){
        define('ROOT_PATH', realpath(dirname(__FILE__) . '/../../'));
        define('CONF_PATH', ROOT_PATH . '/conf');
        define('LOG_PATH', ROOT_PATH . '/log');
        define('MAIN_PATH', ROOT_PATH . '/main');
        define('TMP_PATH', ROOT_PATH . '/tmp');
        define('REPORT_PATH', ROOT_PATH . '/report');
        define('MAIN_CASE_PATH', ROOT_PATH . '/case');
        define('MAIN_LIB_PATH', ROOT_PATH . '/lib');
        define('MAIN_MOCK_PATH', ROOT_PATH . '/mock');
        define('MAIN_RPC_PATH', ROOT_PATH . '/rpc');
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
