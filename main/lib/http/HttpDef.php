<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpDef.php
 * @date 2015/07/14
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class HttpDef {
    const HTTP_CRLF = "\r\n";
    const HTTP_V10 = '1.0';
    const HTTP_V11 = '1.1';
    const HTTP_STATUS_CONTINUE = 100;
    const HTTP_STATUS_SWITCHING_PROTOCOLS = 101;
    const HTTP_STATUS_OK = 200;
    const HTTP_STATUS_CREATED = 201;
    const HTTP_STATUS_ACCEPTED = 202;
    const HTTP_STATUS_NON_AUTHORITATIVE = 203;
    const HTTP_STATUS_NO_CONTENT = 204;
    const HTTP_STATUS_RESET_CONTENT = 205;
    const HTTP_STATUS_PARTIAL_CONTENT = 206;
    const HTTP_STATUS_MULTIPLE_CHOICES = 300;
    const HTTP_STATUS_MOVED_PERMANENTLY = 301;
    const HTTP_STATUS_FOUND = 302;
    const HTTP_STATUS_SEE_OTHER = 303;
    const HTTP_STATUS_NOT_MODIFIED = 304;
    const HTTP_STATUS_USE_PROXY = 305;
    const HTTP_STATUS_TEMPORARY_REDIRECT = 307;
    const HTTP_STATUS_BAD_REQUEST =  400;
    const HTTP_STATUS_UNAUTHORIZED = 401;
    const HTTP_STATUS_FORBIDDEN = 403;
    const HTTP_STATUS_NOT_FOUND = 404;
    const HTTP_STATUS_METHOD_NOT_ALLOWED = 405;
    const HTTP_STATUS_NOT_ACCEPTABLE = 406;
    const HTTP_STATUS_PROXY_AUTH_REQUIRED = 407;
    const HTTP_STATUS_REQUEST_TIMEOUT = 408;
    const HTTP_STATUS_CONFLICT = 409;
    const HTTP_STATUS_GONE = 410;
    const HTTP_STATUS_REQUEST_TOO_LARGE = 413;
    const HTTP_STATUS_URI_TOO_LONG = 414;
    const HTTP_STATUS_SERVER_ERROR = 500;
    const HTTP_STATUS_NOT_IMPLEMENTED = 501;
    const HTTP_STATUS_BAD_GATEWAY = 502;
    const HTTP_STATUS_SERVICE_UNAVAILABLE = 503;
    const HTTP_STATUS_VERSION_NOT_SUPPORTED = 505;

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
