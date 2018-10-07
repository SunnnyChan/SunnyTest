<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file ExpCenter.conf.php
 * @date 2015/06/16
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

final class ExpCenter {
    static $TESTMOD_INFO = array(
        'HOME' => '/home/users/chenguang02/odp',
        //'HOST' => '10.94.51.51',
        'HOST' => '127.0.0.1',
        'PORT' => 8189,
        'PROTOCOL' => 'http',
        'METHOD' => 'post',
        'DATAFORMAT' => 'url-encoded',
        'WRITE_TIMEOUT' => 400,
        'READ_TIMEOUT' => 2500,
        'CONNECT_TIMEOUT' => 300,
    );
    static $HTTP_MOCK_CONF = array(
        'LISTEN_PORT' => 7666,
        'CONN_TYPE'     => 1,
        'WRITE_TIMEOUT'  => 300,
        'READ_TIMEOUT'  => 600,
    );
    static $NSHEAD_MOCK_CONF = array(
        'LISTEN_PORT' => 7666,
        'CONN_TYPE'     => 1,
        'WRITE_TIMEOUT'  => 300,
        'READ_TIMEOUT'  => 600,
    );
    static $LOG_CONF = array(
        'MAX_SIZE'  => '1000',
        'LOG_LEVEL'     => '16',
    );
    static $DB_CONF = array(
        
    );
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
