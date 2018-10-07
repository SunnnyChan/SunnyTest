<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Mocker.php
 * @date 2015/07/06
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

abstract class Mock_Mocker {
    public $name;
    public $host = '127.0.0.1';
    public $listenFlag = false;
    
    public $confArr;
    public $socket;
    public $connection = false;
    
    public function startMock($confArr, $name = 'default server'){
        $this->name = $name;
        if($this->checkConf($confArr)) {
            $this->confArr = $confArr;
            try {
                $this->startListen();
            } catch (Exception $e) {
                Lib_Log::fatal(sprintf("mocker:%s construct failed. error_no:%s error_msg:%s", $this->name, $e->getCode, $e->getMessage));
            }
        } else {
            Lib_Log::fatal(sprintf("mocker:%s construct failed, mocker conf file error.", $name));
        }
    }

    public function checkConf($confArr){
        return  array_key_exists('LISTEN_PORT', $confArr) &&
            array_key_exists('CONN_TYPE', $confArr) &&
            array_key_exists('WRITE_TIMEOUT', $confArr) &&
            array_key_exists('READ_TIMEOUT', $confArr);
    }
    
    public function isListening(){
        return $this->listenFlag ? true : false;
    }
    
    public function startListen(){
        if(false !== $this->listenFlag){
            Lib_Log::warning("$this->name is already listening");
            return true;
        }
        Lib_Log::debug("$this->name starting listen");
        //创建Socket连接
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(false === $this->socket){
            Lib_Log::fatal($this->name . ' init mocker socket failed :' . socket_strerror(socket_last_error()));
            throw new Exception ('start mock error: init server socket failed.', socket_last_error());
        } 
        //设置Socket选项
        if (!socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 0))){
            Lib_Log::fatal($this->name . ' set socket linger failed :' . socket_strerror(socket_last_error()));
            throw new Exception ('start mock error: set socket linger failed.', socket_last_error());
        }
        //绑定端口
        $ret = socket_bind($this->socket, $this->host, $this->confArr['LISTEN_PORT']);
        if(false === $ret) {
            Lib_Log::fatal($this->name . ' bind mocker port failed :' . socket_strerror(socket_last_error()));
            throw new Exception ('start mock error:  bind server port failed.', socket_last_error());
        }
        //监听端口
        $ret = socket_listen($this->socket);
        if(false === $ret) {
            Lib_Log::fatal($this->name . ' listen mocker port failed :' . socket_strerror(socket_last_error()));
            throw new Exception ('start mock error:  bind server port failed.', socket_last_error());
        }
        //设置为非阻塞模式
        $ret = socket_set_nonblock($this->socket);
        if(false === $ret) {
            Lib_Log::fatal($this->name . ' set mocker in non-blocking mode failed :' . socket_strerror(socket_last_error()));
            throw new Exception ('start mock error:  set mocker in non-blocking mode failed.', socket_last_error());
        }

        $this->listenFlag = true;
        Lib_Log::trace(sprintf("mocker:%s start successfully.", $this->name));
        return true;
    } 

    public function accept(){
        if (false === $this->listenFlag){
            Lib_Log::fatal(sprintf("mocker : %s not running.", $this->name));
            throw new Exception ('mocker is not running.');
        }
        //即使以前的connection是ok的，在这里也认为要丢弃了
        if (false !== $this->connection){
            Lib_Log::warning("force close the connection for a new accept");
            $this->closeConn(true);
        }
        //接受连接
        $time = time();
        while(true){
            $this->connection = @socket_accept($this->socket);
            if(false !== $this->connection){
                break;
            } else if (time() - $time >= Def_Sunnytest::MOCK_RECV_TIMEOUT){
                    $this->closeConn(true);
                    Lib_Log::fatal($this->name . 'accecpt connection failed, timeout');
                    throw new Exception('accecpt connection failed, timeout.');
            }
            usleep(100);
        }
        return true;
    }

    public function closeConn($force = false){
        if(false === $this->connection){
            return true;
        }

        Lib_Log::trace(sprintf("closing %s's connection", $this->name));
        socket_close($this->connection);
        $this->connection = false;

        return true;
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
