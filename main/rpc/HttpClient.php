<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Http.php
 * @date 2015/07/01
 * @author sunnnychan@gmail.com
 * @brief
 *
 **/

class Rpc_HttpClient {
    private $host;
    private $port;
    private $uri;
    private $method;
    private $format;
    private $writeTimeout;
    private $readTimeout;
    private $connTimeout;
    //socket 链接
    private $socket;

    private $connected;

    private $keep_alive;
    private $user_agent;
    private $http_version;

    public function __construct($testmodArr, $uri, $keep_alive = false, $http_version){
        $this->host = $testmodArr['HOST'];
        $this->port = $testmodArr['PORT'];
        $this->uri = $uri;
        $this->method = $testmodArr['METHOD'];
        $this->format = $testmodArr['DATAFORMAT'];
        $this->writeTimeout = $testmodArr['WRITE_TIMEOUT'];
        $this->readTimeout = $testmodArr['READ_TIMEOUT'];
        $this->connTimeout = $testmodArr['CONNECT_TIMEOUT'];

        $this->keep_alive = $keep_alive;
        $this->user_agent = 'SocketHttp/1.1 (compatible; MSIE 5.5; Linux)';
        $this->http_version = $http_version;
    }

    /** 为了能在发送和接收数据之间能处理桩，用套接字通信方式把发送数据和接受数据区分开
     *  sendPack 发送数据
     * */
    public function sendPack($sendData){
        if (! $this->connected) {
            $this->connect();
        }
        if ('post' === $this->method){
            return $this->post($sendData);
        } else {
            Lib_Log::fatal('can not dispose http method:%s', $this->method);
            return false;
        }
    }

    public function connect(){
        if (('' === $this->host) || '' === $this->port){
            Lib_Log::fatal('can not connect to server, host or port set invalid.');
            return false;
        }
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->connTimeout);
        if (! $this->socket){
            Lib_Log::fatal('connect to server failed. error_no:%s error_msg;%s', $errno, $errstr);
            throw new exception('connect to server error :' . $errstr, $errno);
        }
        $this->connected = true;
        return true;
    }

    /** 发送POST请求
     *
     * */
    public function post($sendData, $referer = ''){
        if (is_array($sendData)){
            $body = $this->getFormData($sendData);
        } else {
            Lib_Log::warning('form of data to send is invalid.');
            return false;
        }
        $requestMsgObj = new HttpRequestMessage();
        $requestMsgObj->body =  $body . HttpDef::HTTP_CRLF . HttpDef::HTTP_CRLF;
        $contentLen = strlen( $body );
        $requestMsgObj->set_header( 'Host', $this->host );
        $requestMsgObj->set_header( 'Connection', ($this->keep_alive ? 'Keep-Alive' : 'Close') );
        $requestMsgObj->set_header( 'Pragma', 'no-cache' );
        $requestMsgObj->set_header( 'Cache-Control', 'no-cache' );
        $requestMsgObj->set_header( 'Accept', '*/*' );
        $requestMsgObj->set_header( 'Content-Type', 'application/x-www-form-urlencoded' );
        $requestMsgObj->set_header( 'User-Agent', $this->user_agent );
        $requestMsgObj->set_header( 'Content-Length', $contentLen );
        //$requestMsgObj->set_header( 'Cookie', $http_cookie );
        $requestMsgObj->set_header( 'Referer', $referer );

        if ('' !== substr($this->uri, 0, 1)){
            $this->uri = '/' . $this->uri;
        }
        $reqHeader = "POST $this->uri HTTP/" . $this->http_version . HttpDef::HTTP_CRLF .
            $requestMsgObj->serialize_headers() . HttpDef::HTTP_CRLF;
        if (! fwrite( $this->socket, $reqHeader )){
            Lib_Log::fatal('send pack to server failed.');
            throw new  exception('send pack failed.', Def_ErrorNo::ERROR_SOCKET_WRITE);
        }
        usleep( 10 );
        if (! fwrite( $this->socket, $requestMsgObj->body )){
            Lib_Log::fatal('send pack to server failed.');
            throw new exception('send pack failed.', Def_ErrorNo::ERROR_SOCKET_WRITE);
        }
        Lib_Log::debug("data to send (http request pack):\n%s%s", $reqHeader, $requestMsgObj->body);
        return true;
    }

    private function getFormData($sendData){
        Lib_Log::debug('data to send (array):%s', var_export($sendData, true));
        /*
        $formData = array();
        foreach ($sendData as $key => $value){
            $formData[$key] = urlencode($value);
        }
         */
        $formData = http_build_query($sendData);
        Lib_Log::trace("url-encoded data:\n%s", $formData);
        return $formData;
    }

    /** 接受HTTP响应数据
     *  @return exception | true
     * */
    public function getResponse(){
        $responseMsgObj = new HttpResponseMessage();
        $header = '';
        $body = '';
        $continue = true;

        while ($continue){
            $header = '';
            // Read the Response Headers
            while ((($line = fgets($this->socket, 4096)) != HttpDef::HTTP_CRLF || $header == '') && ! feof($this->socket)){
                if ($line != HttpDef::HTTP_CRLF){
                    $header = $header . $line;
                }
            }
            $responseMsgObj->deserialize_headers($header);
            //$responseMsgObj->parse_cookies( $this->host );
            //$responseMsgObj->add_debug_info( $header );
            $continue = ($responseMsgObj->get_status() == HttpDef::HTTP_STATUS_CONTINUE);
            if ($continue) {
                fwrite($this->socket, HttpDef::HTTP_CRLF);
            }
        }

        // Read the Response Body
        $transferEncode = $responseMsgObj->get_header( 'Transfer-Encoding' );
        if ( strtolower( $transferEncode ) != 'chunked' && ! $this->keep_alive ) {
            while ( !feof( $this->socket ) ) {
                $body .= fread( $this->socket, 4096 );
            }
        } else {
            $contentLen = $responseMsgObj->get_header( 'Content-Length' );
            if ( $contentLen != null ) {
                $contentLen = intval($contentLen);
                $body = fread( $this->socket, $contentLen );
            } else {
                if ( $transferEncode != null ) {
                    if ( strtolower( $transferEncode ) == 'chunked' ) {
                        $chunk_size = (integer)hexdec(fgets( $this->socket, 4096 ) );
                        while($chunk_size > 0) {
                            $body .= fread( $this->socket, $chunk_size );
                            fread( $this->socket, strlen(HttpDef::HTTP_CRLF) );
                            $chunk_size = (integer)hexdec(fgets( $this->socket, 4096 ) );
                        }
                        // TODO : Read trailing http headers
                    }
                }
            }
        }
        Lib_Log::trace('HTTP response HEADER : %s', print_r($header, true));
        $responseMsgObj->body = $body;
        return $body;
    }

    /** 关闭连接
     *
     * */
    private function disconnect(){
        Lib_Log::debug('finish interaction with test module, close connection.');
        if ($this->socket && $this->connected) {
            if (! fclose($this->connection)) {
                Lib_Log::fatal('colse connection failed.');
                throw new exception('colse connection failed.', Def_ErrorNo::ERROR_SOCKET_CLOSE);
            }
            $this->connected = false;
        }
    }

    /** 解析http响应，返回body
     *
     * */
    private function parseHttpResponse($httpRes){
        return false;
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
