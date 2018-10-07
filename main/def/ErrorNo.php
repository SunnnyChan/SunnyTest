<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file ErrorNo.php
 * @date 2015/07/06
 * @author chenguang02@baidu.com
 * @brief
 *
 **/

class Def_ErrorNo {
    const ERROR_INIT_FRAME_PARSE_ARG = -1001;

    const ERROR_LOAD_CASE_DATA_FAILED = 2001;
    const ERROR_RUN_CASE_LOAD_OR_CHECK_CASE_DATA = 3001;
    const ERROR_RUN_CASE_SEND_REQ = 3002;
    const ERROR_RUN_CASE_RECV_RES = 3003;
    const ERROR_RUN_CASE_CHECK_RES = 3004;

    const ERROR_MOCK_CONF = 6002;

    const ERROR_SOCKET_CONNECT = 9006;
    const ERROR_SOCKET_READ = 9007;
    const ERROR_SOCKET_WRITE = 9008;
    const ERROR_SOCKET_CLOSE = 9009;

    public static $err_info = array(
        self::ERROR_LOAD_CASE_DATA_FAILED => 'load case file failed.',
    );

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
