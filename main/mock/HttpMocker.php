<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpMocker.php
 * @date 2015/07/06
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class Mock_HttpMocker extends Mock_Mocker {
    private static $instance = false;
    private function __construct(){

    }

    private function __clone(){

    }

    public static function getIns(){
        if (! self::$instance instanceof self){
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    public function recvPack(){
        return false;
    }

    public function sendPack(){
        return false;
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
