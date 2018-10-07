<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpResponseMessage.php
 * @date 2015/07/14
 * @author sunnnychan@gmail.com
 * @brief 
 *
 **/

class HttpResponseMessage extends HttpResponseHeader {
    public $body;
    public $cookies;

    public function http_response_message() {
        $this->cookies = new HttpCookie();
        $this->body = '';
        HttpResponseHeader::http_response_header();
    } // End of function http_response_message()

    public function get_status() {
        if ( $this->get_header( 'Status' ) != null )
            return (integer)$this->get_header( 'Status' );
        else
            return -1;
    }

    public function get_protocol_version() {
        if ( $this->get_header( 'Protocol-Version' ) != null )
            return $this->get_header( 'Protocol-Version' );
        else
            return HTTP_V10;
    }

    public function get_content_type() {
        $this->get_header('Content-Type');
        $this->get_header('X-BFB-RT');
    }

    public function get_body() {
        return $this->body;
    }

    public function reset() {
        $this->body = '';
        HttpResponseHeader::reset();
    }

    public function parse_cookies( $host ) {
        for ( $i = 0; $i < count( $this->cookies_headers ); $i++ )
            $this->cookies->parse( $this->cookies_headers[$i], $host );
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
