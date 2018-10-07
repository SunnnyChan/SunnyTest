<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file MockConf.php
 * @date 2015/06/16
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

final class MockConf {
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
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
