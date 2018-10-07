<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Http.php
 * @date 2015/07/14
 * @author sunnnychan@gmail.com
 * @brief
 *
 **/

class Http {
        public $_socket;
        public $host;
        public $port;
        public $http_version;
        public $user_agent;
        public $errstr;
        public $connected;
        public $uri;
        public $_proxy_host;
        public $_proxy_port;
        public $_proxy_login;
        public $_proxy_pwd;
        public $_use_proxy;
        public $_auth_login;
        public $_auth_pwd;
        public $_response;
        public $_request;
        public $_keep_alive;

        public function http( $http_version = HTTP_V10, $keep_alive = false, $auth = false ) {
            $this->http_version = $http_version;
            $this->connected    = false;
            $this->user_agent   = 'CosmoHttp/1.1 (compatible; MSIE 5.5; Linux)';
            $this->host         = '';
            $this->port         = 80;
            $this->errstr       = '';

            $this->_keep_alive  = $keep_alive;
            $this->_proxy_host  = '';
            $this->_proxy_port  = -1;
            $this->_proxy_login = '';
            $this->_proxy_pwd   = '';
            $this->_auth_login  = '';
            $this->_auth_pwd    = '';
            $this->_use_proxy   = false;
            $this->_response    = new http_response_message();
            $this->_request     = new http_request_message();

        // Basic Authentification added by Mate Jovic, 2002-18-06, jovic@matoma.de
            if( is_array($auth) && count($auth) == 2 ){
                $this->_auth_login  = $auth[0];
                $this->_auth_pwd    = $auth[1];
            }
        } // End of Constuctor

        public function use_proxy( $host, $port, $proxy_login = null, $proxy_pwd = null ) {
        // Proxy auth not yet supported
            $this->http_version = HTTP_V10;
            $this->_keep_alive  = false;
            $this->_proxy_host  = $host;
            $this->_proxy_port  = $port;
            $this->_proxy_login = $proxy_login;
            $this->_proxy_pwd   = $proxy_pwd;
            $this->_use_proxy   = true;
        }

        public function set_request_header( $name, $value ) {
            $this->_request->set_header( $name, $value );
        }

        public function get_response_body() {
            return $this->_response->body;
        }

        public function get_response() {
            return $this->_response;
        }

        public function head( $uri ) {
            $this->uri = $uri;

            if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
                if ( !$this->_connect() ) {
                    $this->errstr = 'Could not connect to ' . $this->host;
                    return -1;
                }
            }
            $http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );

            if ($this->_use_proxy) {
                $this->_request->set_header( 'Host', $this->host . ':' . $this->port );
                $this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                if ( $this->_proxy_login != '' ) {
                    $this->_request->set_header( 'Proxy-Authorization', "Basic " .
                        base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
                }
                $uri = 'http://' . $this->host . ':' . $this->port . $uri;
            } else {
                $this->_request->set_header( 'Host', $this->host );
                $this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
            }

            if ( $this->_auth_login != '' ) {
                $this->_request->set_header( 'Authorization', "Basic " .
                    base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
            }
            $this->_request->set_header( 'User-Agent', $this->user_agent );
            $this->_request->set_header( 'Accept', '*/*' );
            $this->_request->set_header( 'Cookie', $http_cookie );

            $cmd =  "HEAD $uri HTTP/" . $this->http_version . HTTP_CRLF .
                    $this->_request->serialize_headers() .
                    HTTP_CRLF;
            fwrite( $this->_socket, $cmd );

            $this->_request->add_debug_info( $cmd );
            $this->_get_response( false );

            if ($this->_socket && !$this->_keep_alive) $this->disconnect();
            if ( $this->_response->get_header( 'Connection' ) != null ) {
                if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
                    $this->_keep_alive = false;
                    $this->disconnect();
                }
            }

            if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
                $location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
                $this->disconnect();
                $this->use_proxy( $location['host'], $location['port'] );
                $this->head( $this->uri );
            }

            return $this->_response->get_header( 'Status' );
        } // End of function head()


        public function get( $uri, $follow_redirects = true, $referer = '' ) {
            $this->uri = $uri;

            if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
                if ( !$this->_connect() ) {
                    $this->errstr = 'Could not connect to ' . $this->host;
                    return -1;
                }
            }

            if ($this->_use_proxy) {
                $this->_request->set_header( 'Host', $this->host . ':' . $this->port );
                $this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                if ( $this->_proxy_login != '' ) {
                    $this->_request->set_header( 'Proxy-Authorization', "Basic " .
                        base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
                }
                $uri = 'http://' . $this->host . ':' . $this->port . $uri;
            } else {
                $this->_request->set_header( 'Host', $this->host );
                $this->_request->set_header( 'Connection', ($this->_keep_alive ? 'Keep-Alive' : 'Close') );
                $this->_request->set_header( 'Pragma', 'no-cache' );
                $this->_request->set_header( 'Cache-Control', 'no-cache' );
            }

            if ( $this->_auth_login != '' ) {
                $this->_request->set_header( 'Authorization', "Basic " .
                    base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
            }
            $http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
            $this->_request->set_header( 'User-Agent', $this->user_agent );
            $this->_request->set_header( 'Accept', '*/*' );
            $this->_request->set_header( 'Referer', $referer );
            $this->_request->set_header( 'Cookie', $http_cookie );

            $cmd =  "GET $uri HTTP/" . $this->http_version . HTTP_CRLF .
                    $this->_request->serialize_headers() .
                    HTTP_CRLF;
            fwrite( $this->_socket, $cmd );

            $this->_request->add_debug_info( $cmd );
            $this->_get_response();

            if ($this->_socket && !$this->_keep_alive) $this->disconnect();
            if (  $this->_response->get_header( 'Connection' ) != null ) {
                if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
                    $this->_keep_alive = false;
                    $this->disconnect();
                }
            }
            if ( $follow_redirects
                && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY
                    || $this->_response->get_status() == HTTP_STATUS_FOUND
                    || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
                if ( $this->_response->get_header( 'Location' ) != null  ) {
                    $this->_redirect( $this->_response->get_header( 'Location' ) );
                }
            }

            if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
                $location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
                $this->disconnect();
                $this->use_proxy( $location['host'], $location['port'] );
                $this->get( $this->uri, $referer );
            }

            return $this->_response->get_status();
        } // End of function get()

        public function multipart_post( $uri, &$form_fields, $form_files = null, $follow_redirects = true, $referer = '' ) {
            $this->uri = $uri;

            if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
                if ( !$this->_connect() ) {
                    $this->errstr = 'Could not connect to ' . $this->host;
                    return -1;
                }
            }
            $boundary = uniqid('------------------');
            $http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
            $body = $this->_merge_multipart_form_data( $boundary, $form_fields, $form_files );
            $this->_request->body =  $body . HTTP_CRLF;
            $content_length = strlen( $body );


            if ($this->_use_proxy) {
                $this->_request->set_header( 'Host', $this->host . ':' . $this->port );
                $this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic "
                    . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
                $uri = 'http://' . $this->host . ':' . $this->port . $uri;
            } else {
                $this->_request->set_header( 'Host', $this->host );
                $this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                $this->_request->set_header( 'Pragma', 'no-cache' );
                $this->_request->set_header( 'Cache-Control', 'no-cache' );
            }

            if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic "
                . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
            $this->_request->set_header( 'Accept', '*/*' );
            $this->_request->set_header( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
            $this->_request->set_header( 'User-Agent', $this->user_agent );
            $this->_request->set_header( 'Content-Length', $content_length );
            $this->_request->set_header( 'Cookie', $http_cookie );
            $this->_request->set_header( 'Referer', $referer );

            $req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF .
                        $this->_request->serialize_headers() .
                        HTTP_CRLF;

            fwrite( $this->_socket, $req_header );
            usleep(10);
            fwrite( $this->_socket, $this->_request->body );

            $this->_request->add_debug_info( $req_header );
            $this->_get_response();

            if ($this->_socket && !$this->_keep_alive) $this->disconnect();
            if ( $this->_response->get_header( 'Connection' ) != null ) {
                if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
                    $this->_keep_alive = false;
                    $this->disconnect();
                }
            }

            if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
                if ( $this->_response->get_header( 'Location') != null ) {
                    $this->_redirect( $this->_response->get_header( 'Location') );
                }
            }

            if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
                $location = $this->_parse_location( $this->_response->get_header( 'Location') );
                $this->disconnect();
                $this->use_proxy( $location['host'], $location['port'] );
                $this->multipart_post( $this->uri, $form_fields, $form_files, $referer );
            }

            return $this->_response->get_status();
        } // End of function multipart_post()

        public function post( $uri, &$form_data, $follow_redirects = true, $referer = '' ) {
            $this->uri = $uri;

            if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
                if ( !$this->_connect() ) {
                    $this->errstr = 'Could not connect to ' . $this->host;
                    return -1;
                }
            }
            $http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
            $body = substr( $this->_merge_form_data( $form_data ), 1 );
            $this->_request->body =  $body . HTTP_CRLF . HTTP_CRLF;
            $content_length = strlen( $body );

            if ($this->_use_proxy) {
                $this->_request->set_header( 'Host', $this->host . ':' . $this->port );
                $this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
                $uri = 'http://' . $this->host . ':' . $this->port . $uri;
            } else {
                $this->_request->set_header( 'Host', $this->host );
                $this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                $this->_request->set_header( 'Pragma', 'no-cache' );
                $this->_request->set_header( 'Cache-Control', 'no-cache' );
            }

            if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
            $this->_request->set_header( 'Accept', '*/*' );
            $this->_request->set_header( 'Content-Type', 'application/x-www-form-urlencoded' );
            $this->_request->set_header( 'User-Agent', $this->user_agent );
            $this->_request->set_header( 'Content-Length', $content_length );
            $this->_request->set_header( 'Cookie', $http_cookie );
            $this->_request->set_header( 'Referer', $referer );

            $req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF .
                        $this->_request->serialize_headers() .
                        HTTP_CRLF;

            fwrite( $this->_socket, $req_header );
            usleep( 10 );
            fwrite( $this->_socket, $this->_request->body );

            $this->_request->add_debug_info( $req_header );
            $this->_get_response();

            if ($this->_socket && !$this->_keep_alive) $this->disconnect();
            if ( $this->_response->get_header( 'Connection' ) != null ) {
                if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
                    $this->_keep_alive = false;
                    $this->disconnect();
                }
            }

            if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
                if ( $this->_response->get_header( 'Location' ) != null ) {
                    $this->_redirect( $this->_response->get_header( 'Location' ) );
                }
            }

            if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
                $location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
                $this->disconnect();
                $this->use_proxy( $location['host'], $location['port'] );
                $this->post( $this->uri, $form_data, $referer );
            }

            return $this->_response->get_status();
        } // End of function post()

        public function post_xml( $uri, $xml_data, $follow_redirects = true, $referer = '' ) {
            $this->uri = $uri;

            if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
                if ( !$this->_connect() ) {
                    $this->errstr = 'Could not connect to ' . $this->host;
                    return -1;
                }
            }
            $http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
            $body = $xml_data;
            $this->_request->body =  $body . HTTP_CRLF . HTTP_CRLF;
            $content_length = strlen( $body );

            if ($this->_use_proxy) {
                $this->_request->set_header( 'Host', $this->host . ':' . $this->port );
                $this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
                $uri = 'http://' . $this->host . ':' . $this->port . $uri;
            } else {
                $this->_request->set_header( 'Host', $this->host );
                $this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
                $this->_request->set_header( 'Pragma', 'no-cache' );
                $this->_request->set_header( 'Cache-Control', 'no-cache' );
            }

            if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
            $this->_request->set_header( 'Accept', '*/*' );
            $this->_request->set_header( 'Content-Type', 'text/xml; charset=utf-8' );
            $this->_request->set_header( 'User-Agent', $this->user_agent );
            $this->_request->set_header( 'Content-Length', $content_length );
            $this->_request->set_header( 'Cookie', $http_cookie );
            $this->_request->set_header( 'Referer', $referer );

            $req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF .
                        $this->_request->serialize_headers() .
                        HTTP_CRLF;

            fwrite( $this->_socket, $req_header );
            usleep( 10 );
            fwrite( $this->_socket, $this->_request->body );

            $this->_request->add_debug_info( $req_header );
            $this->_get_response();

            if ($this->_socket && !$this->_keep_alive) $this->disconnect();
            if ( $this->_response->get_header( 'Connection' ) != null ) {
                if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
                    $this->_keep_alive = false;
                    $this->disconnect();
                }
            }

            if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
                if ( $this->_response->get_header( 'Location' ) != null ) {
                    $this->_redirect( $this->_response->get_header( 'Location' ) );
                }
            }

            if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
                $location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
                $this->disconnect();
                $this->use_proxy( $location['host'], $location['port'] );
                $this->post( $this->uri, $form_data, $referer );
            }

            return $this->_response->get_status();
        } // End of function post_xml()


        public function disconnect() {
            if ($this->_socket && $this->connected) {
                 fclose($this->_socket);
                $this->connected = false;
             }
        } // End of function disconnect()

        /********************************************************************************
         * Private functions
         ********************************************************************************/
        private function _connect( ) {
            if ( $this->host == '' ) user_error( 'Class HTTP->_connect() : host property not set !' , E_ERROR );
            if (!$this->_use_proxy)
                $this->_socket = fsockopen( $this->host, $this->port, $errno, $errstr, 10 );
            else
                $this->_socket = fsockopen( $this->_proxy_host, $this->_proxy_port, $errno, $errstr, 10 );
            $this->errstr  = $errstr;
            $this->connected = ($this->_socket == true);
            return $this->connected;
        } // End of function connect()


        private function _merge_multipart_form_data( $boundary, &$form_fields, &$form_files ) {
            $boundary = '--' . $boundary;
            $multipart_body = '';
            foreach ( $form_fields as $name => $data) {
                $multipart_body .= $boundary . HTTP_CRLF;
                $multipart_body .= 'Content-Disposition: form-data; name="' . $name . '"' . HTTP_CRLF;
                $multipart_body .=  HTTP_CRLF;
                $multipart_body .= $data . HTTP_CRLF;
            }
            if ( isset($form_files) ) {
                foreach ( $form_files as $data) {
                    $multipart_body .= $boundary . HTTP_CRLF;
                    $multipart_body .= 'Content-Disposition: form-data; name="' . $data['name'] . '"; filename="' . $data['filename'] . '"' . HTTP_CRLF;
                    if ($data['content-type']!='')
                        $multipart_body .= 'Content-Type: ' . $data['content-type'] . HTTP_CRLF;
                    else
                        $multipart_body .= 'Content-Type: application/octet-stream' . HTTP_CRLF;
                    $multipart_body .=  HTTP_CRLF;
                    $multipart_body .= $data['data'] . HTTP_CRLF;
                }
            }
            $multipart_body .= $boundary . '--' . HTTP_CRLF;
            return $multipart_body;
        } // End of function _merge_multipart_form_data()


        private function _merge_form_data( &$param_array,  $param_name = '' ) {
            $params = '';
            $format = ($param_name !=''?'&'.$param_name.'[%s]=%s':'&%s=%s');
            foreach ( $param_array as $key=>$value ) {
                if ( !is_array( $value ) )
                    $params .= sprintf( $format, $key, urlencode( $value ) );
                else
                    $params .= $this->_merge_form_data( $param_array[$key],  $key );
            }
            return $params;
        } // End of function _merge_form_data()

        private function _current_directory( $uri ) {
            $tmp = split( '/', $uri );
            array_pop($tmp);
            $current_dir = implode( '/', $tmp ) . '/';
            return ($current_dir!=''?$current_dir:'/');
        } // End of function _current_directory()


        private function _get_response( $get_body = true ) {
            $this->_response->reset();
            $this->_request->reset();
            $header = '';
            $body = '';
            $continue   = true;

            while ($continue) {
                $header = '';

                // Read the Response Headers
                while ( (($line = fgets( $this->_socket, 4096 )) != HTTP_CRLF || $header == '') && !feof( $this->_socket ) ) {
                    if ($line != HTTP_CRLF) $header .= $line;
                }
                $this->_response->deserialize_headers( $header );
                $this->_response->parse_cookies( $this->host );

                $this->_response->add_debug_info( $header );
                $continue = ($this->_response->get_status() == HTTP_STATUS_CONTINUE);
                if ($continue) fwrite( $this->_socket, HTTP_CRLF );
            }

            if ( !$get_body ) return;

            // Read the Response Body
            if ( strtolower( $this->_response->get_header( 'Transfer-Encoding' ) ) != 'chunked' && !$this->_keep_alive ) {
                while ( !feof( $this->_socket ) ) {
                    $body .= fread( $this->_socket, 4096 );
                }
            } else {
                if ( $this->_response->get_header( 'Content-Length' ) != null ) {
                    $content_length = (integer)$this->_response->get_header( 'Content-Length' );
                    $body = fread( $this->_socket, $content_length );
                } else {
                    if ( $this->_response->get_header( 'Transfer-Encoding' ) != null ) {
                        if ( strtolower( $this->_response->get_header( 'Transfer-Encoding' ) ) == 'chunked' ) {
                            $chunk_size = (integer)hexdec(fgets( $this->_socket, 4096 ) );
                            while($chunk_size > 0) {
                                $body .= fread( $this->_socket, $chunk_size );
                                fread( $this->_socket, strlen(HTTP_CRLF) );
                                $chunk_size = (integer)hexdec(fgets( $this->_socket, 4096 ) );
                            }
                            // TODO : Read trailing http headers
                        }
                    }
                }
            }
            $this->_response->body = $body;
        } // End of function _get_response()

        private function _parse_location( $redirect_uri ) {
            $parsed_url     = parse_url( $redirect_uri );
            $scheme         = (isset($parsed_url['scheme'])?$parsed_url['scheme']:'');
            $port           = (isset($parsed_url['port'])?$parsed_url['port']:$this->port);
            $host           = (isset($parsed_url['host'])?$parsed_url['host']:$this->host);
            $request_file   = (isset($parsed_url['path'])?$parsed_url['path']:'');
            $query_string   = (isset($parsed_url['query'])?$parsed_url['query']:'');
            if ( substr( $request_file, 0, 1 ) != '/' )
                $request_file = $this->_current_directory( $this->uri ) . $request_file;

            return array(   'scheme' => $scheme,
                            'port' => $port,
                            'host' => $host,
                            'request_file' => $request_file,
                            'query_string' => $query_string
            );

        } // End of function _parse_location()

        private function _redirect( $uri ) {
            $location = $this->_parse_location( $uri );
            if ( $location['host'] != $this->host || $location['port'] != $this->port ) {
                $this->host = $location['host'];
                $this->port = $location['port'];
                if ( !$this->_use_proxy) $this->disconnect();
            }
            usleep( 100 );
            $this->get( $location['request_file'] . '?' . $location['query_string'] );
        } // End of function _redirect()
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
