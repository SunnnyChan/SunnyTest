<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpResponseHeader.php
 * @date 2015/07/14
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class HttpResponseHeader extends HttpHeader{
    public $cookies_headers;
    
    public function http_response_header() {
        $this->cookies_headers = array();
        http_header::http_header();
    } // End of function http_response_header()
    
    public function deserialize_headers( $flat_headers ) {
        $flat_headers = preg_replace( "/^" . HttpDef::HTTP_CRLF . "/", '', $flat_headers );
        $tmp_headers = split(HttpDef::HTTP_CRLF, $flat_headers );
        if (preg_match("'HTTP/(\d\.\d)\s+(\d+).*'i", $tmp_headers[0], $matches )) {
            $this->set_header( 'Protocol-Version', $matches[1] );
            $this->set_header( 'Status', $matches[2] );
        } 
        array_shift( $tmp_headers );
        foreach( $tmp_headers as $index=>$value ) {
            $pos = strpos( $value, ':' );
            if ( $pos ) {
                $key = substr( $value, 0, $pos );
                $value = trim( substr( $value, $pos +1) );
                if ( strtoupper($key) == 'SET-COOKIE' )
                    $this->cookies_headers[] = $value;
                else
                    $this->set_header( $key, $value );
            }
        }
    } // End of function deserialize_headers()
    
    public function reset() {
        if ( count( $this->cookies_headers ) > 0 ){
            $this->cookies_headers = array();
        }
        http_header::reset();
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
