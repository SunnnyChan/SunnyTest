<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpHeader.php
 * @date 2015/07/14
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class HttpHeader {
    public $_headers;
    public $_debug;

    public function http_header() {
        $this->_headers = Array();
        $this->_debug   = '';
    } // End Of function http_header()
    
    public function get_header( $header_name ) {
        $header_name = $this->format_header_name( $header_name );
        if (isset($this->_headers[$header_name])){
            return $this->_headers[$header_name];
        } else {
            return null;
        }
    } // End of function get()
    
    public function set_header( $header_name, $value ) {
        if ($value != '') {
            $header_name = $this->format_header_name( $header_name );
            $this->_headers[$header_name] = $value;
        }
    } // End of function set()
    
    public function reset() {
        if ( count( $this->_headers ) > 0 ){
            $this->_headers = array();
        }
        $this->_debug   .= "\n--------------- RESETED ---------------\n";
    } // End of function clear()

    public function serialize_headers() {
        $str = '';
        foreach ( $this->_headers as $name=>$value) {
            $str .= "$name: $value" . HttpDef::HTTP_CRLF;
        }
        return $str;
    } // End of function serialize_headers()
    
    public function format_header_name( $header_name ) {
        $formatted = str_replace( '-', ' ', strtolower( $header_name ) );
        $formatted = ucwords( $formatted );
        $formatted = str_replace( ' ', '-', $formatted );
        return $formatted;
    }
    
    public function add_debug_info( $data ) {
        $this->_debug .= $data;
    }

    public function get_debug_info() {
        return $this->_debug;
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
