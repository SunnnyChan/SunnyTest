<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpRequestMessage.php
 * @date 2015/07/14
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class HttpRequestMessage extends HttpHeader {
        public $body;

        public function http_request_message() {
            $this->body = '';
            $this->http_header();
        } // End of function http_message()

        public function reset() {
            $this->body = '';
            $this->reset();
        }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
