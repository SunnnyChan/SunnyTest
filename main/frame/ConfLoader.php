<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file ConfLoader.php
 * @date 2015/06/30
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class ConfLoader{
    private static $instance;
    public $logConf = '';
    public $testmodConf = '';
    public $httpMockConf = '';
    public $nsheadMockConf = '';
    public $dbConf = '';

    private function __construct(){
    
    }

    private function __clone(){
       return false; 
    }

    public static function getInstance(){
        if ( ! self::$instance instanceof self ){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function loadTestmodConf($confClass){
        $confClassReflect = new ReflectionClass($confClass);
        $confClassStaticPropertiesArr = $confClassReflect->getStaticProperties();
        $this->logConf = $confClassStaticPropertiesArr['LOG_CONF']; 
        $this->testmodConf = $confClassStaticPropertiesArr['TESTMOD_INFO']; 
        $this->httpMockConf = $confClassStaticPropertiesArr['HTTP_MOCK_CONF']; 
        $this->nsheadMockConf = $confClassStaticPropertiesArr['NSHEAD_MOCK_CONF']; 
        $this->dbConf = $confClassStaticPropertiesArr['DB_CONF']; 
    }

    public function loadMockConf($confClass){
        $confClassReflect = new ReflectionClass($confClass);
        $confClassStaticPropertiesArr = $confClassReflect->getStaticProperties();
        $this->httpMockConf = $confClassStaticPropertiesArr['HTTP_MOCK_CONF']; 
        $this->nsheadMockConf = $confClassStaticPropertiesArr['NSHEAD_MOCK_CONF']; 
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
