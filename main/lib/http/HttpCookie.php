<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file HttpCookie.php
 * @date 2015/07/14
 * @author sunnnychan@gmail.com
 * @brief 
 *
 **/

class HttpCookie {
    public $cookies;

    public function http_cookie() {
        $this->cookies  = array();
    } // End of function http_cookies()
    
    public function _now() {
        return strtotime( gmdate( "l, d-F-Y H:i:s", time() ) );
    } // End of function _now()
    
    public function _timestamp( $date ) {
        if ( $date == '' ) return $this->_now()+3600;
        $time = strtotime( $date );
        return ($time>0?$time:$this->_now()+3600);
    } // End of function _timestamp()

    public function get( $current_domain, $current_path ) {
        $cookie_str = '';
        $now = $this->_now();
        $new_cookies = array();

        foreach( $this->cookies as $cookie_name => $cookie_data ) {
            if ($cookie_data['expires'] > $now) {
                $new_cookies[$cookie_name] = $cookie_data;
                $domain = preg_quote( $cookie_data['domain'] );
                $path = preg_quote( $cookie_data['path']  );
                if ( preg_match( "'.*$domain$'i", $current_domain ) && preg_match( "'^$path.*'i", $current_path ) )
                    $cookie_str .= $cookie_name . '=' . $cookie_data['value'] . '; ';
            }
        }
        $this->cookies = $new_cookies;
        return $cookie_str;
    } // End of function get()
    
    public function set( $name, $value, $domain, $path, $expires ) {
        $this->cookies[$name] = array(  'value' => $value,
                                        'domain' => $domain,
                                        'path' => $path,
                                        'expires' => $this->_timestamp( $expires )
                                        );
    } // End of function set()
    
    public function parse( $cookie_str, $host ) {
        $cookie_str = str_replace( '; ', ';', $cookie_str ) . ';';
        $data = split( ';', $cookie_str );
        $value_str = $data[0];

        $cookie_param = 'domain=';
        $start = strpos( $cookie_str, $cookie_param );
        if ( $start > 0 ) {
            $domain = substr( $cookie_str, $start + strlen( $cookie_param ) );
            $domain = substr( $domain, 0, strpos( $domain, ';' ) );
        } else
            $domain = $host;

        $cookie_param = 'expires=';
        $start = strpos( $cookie_str, $cookie_param );
        if ( $start > 0 ) {
            $expires = substr( $cookie_str, $start + strlen( $cookie_param ) );
            $expires = substr( $expires, 0, strpos( $expires, ';' ) );
        } else
            $expires = '';
        
        $cookie_param = 'path=';
        $start = strpos( $cookie_str, $cookie_param );
        if ( $start > 0 ) {
            $path = substr( $cookie_str, $start + strlen( $cookie_param ) );
            $path = substr( $path, 0, strpos( $path, ';' ) );
        } else
            $path = '/';
                        
        $sep_pos = strpos( $value_str, '=');
        
        if ($sep_pos){
            $name = substr( $value_str, 0, $sep_pos );
            $value = substr( $value_str, $sep_pos+1 );
            $this->set( $name, $value, $domain, $path, $expires );
        }
    } // End of function parse()

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
