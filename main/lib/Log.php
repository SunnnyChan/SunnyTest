<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Log.php
 * @date 2015/06/29
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class Lib_Log {
    const LOG_FATAL   = 1;
    const LOG_WARNING = 2;
    const LOG_MONITOR = 3;
    const LOG_NOTICE  = 4;
    const LOG_TRACE   = 8;
    const LOG_DEBUG   = 16;
    const PAGE_SIZE   = 4096;
    const LOG_SPACE   = "\10";
    const MONTIR_STR  = ' ---LOG_MONITOR---';

    static $DO_LOG_LINE_NO = array (
        self::LOG_FATAL   => true,
        self::LOG_WARNING => true,
        self::LOG_MONITOR => true,
        self::LOG_NOTICE  => false,
        self::LOG_TRACE   => false,
        self::LOG_DEBUG   => true
    );

    static $LOG_NAME = array (
        self::LOG_FATAL   => 'FATAL',
        self::LOG_WARNING => 'WARNING',
        self::LOG_MONITOR => 'MONITOR',
        self::LOG_NOTICE  => 'NOTICE',
        self::LOG_TRACE   => 'TRACE',
        self::LOG_DEBUG   => 'DEBUG'
    );

    static $BASIC_FIELD = array (
        'logid',
        //'logid_r', //logid for rpc
        //'reqip',
        //'uid',
        //'uname',
        //'method',
        //'uri'
    );
    //保存logId 用数组是为了支持多进程并发执行case的情况
    static $logId = array();

    //日志名
    private $log_name   = '';
    //正常日志全路径 
    private $log_path   = '';
    //wf日志全路径 
    private $wflog_path = '';
    private $log_str    = '';
    private $wflog_str  = '';
    private $basic_info = '';
    private $notice_str = '';
    private $log_level  = 16;
    private $arr_basic  = null;

    //force_flush 是否强制写出
    private $force_flush = false;

    //init_pid 初始化时的pid
    private $init_pid   = 0;

    function __construct()
    {
    }

    function __destruct()
    {
        if ($this->init_pid == posix_getpid()) {
            //只在打出当前进程的日志 
            $this->check_flush_log(true);
        }
    }

    function init($dir, $name, $level, $arr_basic_info, $flush=false)
    {
        if (empty($dir) || empty($name)) {
            return false;
        }

        //使用的为绝对路径 
        if ('/'!= $dir{0}) {
            $dir = realpath($dir);
        }

        $dir  = rtrim($dir, ".");
        $name = rtrim($name, "/");
        $this->log_path   = $dir . "/" . $name .".log";
        $this->wflog_path = $dir . "/" . $name . ".log.wf"; 
        $this->log_name  = $name;
        $this->log_level = $level;

        /* set basic info */
        $this->arr_basic = $arr_basic_info;
        /* 生成basic info的字符串 */
        $this->gen_basicinfo();
        /* 记录初使化进程的id */
        $this->init_pid = posix_getpid();
        $this->force_flush = $flush;

        return true;
    }

    private function gen_log_part($str)
    {
        return "[ " . self::LOG_SPACE . $str . " ". self::LOG_SPACE . "]";
    }

    private function gen_basicinfo()
    {
        $this->basic_info = '';
        foreach (self::$BASIC_FIELD as $key) {
            if (!empty($this->arr_basic[$key])) {
                $this->basic_info .= $this->gen_log_part("$key:".$this->arr_basic[$key]) . " ";
            }
        }
    }

    private function check_flush_log($force_flush)
    {
        if (strlen($this->log_str) > self::PAGE_SIZE ||
            strlen($this->wflog_str) > self::PAGE_SIZE ) {
            $force_flush = true;
        }

        if ($force_flush) {
            /* first write warning log */
            if (! empty($this->wflog_str)) {
                $this->write_file($this->wflog_path, $this->wflog_str);
            }
            /* then common log */
            if (! empty($this->log_str)) {
                $this->write_file($this->log_path, $this->log_str);
            }

            /* clear the printed log*/
            $this->wflog_str = '';
            $this->log_str   = '';

        } /* force_flush */
    }


    private function write_file($path, $str){
        $fd = @fopen($path, "a+" );
        if (is_resource($fd)) {
            fputs($fd, $str);
            fclose($fd);
        }
        return;
    }

    public function add_basicinfo($arr_basic_info){
        $this->arr_basic = array_merge($this->arr_basic, $arr_basic_info);
        $this->gen_basicinfo();
    }

    public function push_notice($format, $arr_data){
        $this->notice_str .= " " .$this->gen_log_part(vsprintf($format, $arr_data));
    }

    public function clear_notice(){
        $this->notice_str = '';
    }

    public function write_log($type, $format, $arr_data){
        if ($this->log_level < $type)
            return;

        /* log heading */
        $str = sprintf( "%s: %s: %s: ", self::$LOG_NAME[$type], date("m-d H:i:s"),
              $this->log_name);
        /* add monitor tag? */  
        if ($type == self::LOG_MONITOR || $type == self::LOG_FATAL) {
            $str .= self::MONTIR_STR;
        }
        /* add basic log */
        $str .= $this->basic_info;
        /* add detail log */
        if ( null==$arr_data ) {
            $str .= " " . vsprintf('%s', $format);
        } else {
            $str .= " " . vsprintf($format, $arr_data);
        }

        switch ($type) {
            case self::LOG_MONITOR :
            case self::LOG_FATAL :
            case self::LOG_WARNING :
            case self::LOG_FATAL :
                $this->wflog_str .= $str . "\n";
                break;
            case self::LOG_DEBUG :
            case self::LOG_TRACE :
                $this->log_str .= $str . "\n";
                break;
            case self::LOG_NOTICE :
                $this->log_str .= $str . $this->notice_str . "\n";
                $this->clear_notice();
                break;
            default :
                break;
        }

        $this->check_flush_log($this->force_flush);
    }

    public static function ubLog($type, $arr){
        $format = $arr[0];
        array_shift($arr);

        $pid = posix_getpid();
        $line_no="";

        if(self::$DO_LOG_LINE_NO[$type]){
            $bt = debug_backtrace();
            //函数调用层级 0-> log func , 1-> user func
            if(isset($bt[1])){ 
                $c = $bt[1];
            }else if (isset($bt[0])){
                $c = $bt[0];
            } else {
                $c = array('file'=>'faint', 'line'=>'faint');
            }
            $line_no='[' . $c['file'] . ':' . $c['line'].'] ';
        }

        $format = $line_no . $format;

        if (!empty(self::$logId[$pid])) {
            /* shift $type and $format, arr_data left */
            $libLogObj = self::$logId[$pid];
            $libLogObj->write_log($type, $format, $arr);
        } else {
            /* print out to stderr, open the comment by yanpeng 2010-08-31*/
            $s =  self::$LOG_NAME[$type] . ' ' . vsprintf($format, $arr) . "\n";
            echo $s;
        } /* if $__log */
    }

    /**
     * logInit Log初始化 
     * @param string $dir      目录名
     * @param string $file     日志名
     * @param interger $level  日志级别 
     * @param array $info      日志基本信息,可以参考__mc_log::$BASIC_FIELD  
     * @param bool  $flush     是否日志直接flush到硬盘,默认会有4K的缓冲
     * @return boolean          true成功;false失败
     */
    public static function logInit($dir, $file, $level, $info, $flush=false){
        $pid = posix_getpid();
    
        if (! empty(self::$logId[$pid]) ) {
            unset(self::$logId[$pid]);
        }
    
        self::$logId[posix_getpid()] = new self(); 
        $libLogObj = self::$logId[posix_getpid()];
        if ($libLogObj->init($dir, $file, $level, $info, $flush)) {
            return true;
        } else {
            unset($logId[$pid]);
            return false;
        }
    }
    
    /**
     * debug                   DEBUG日志
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function debug(){
        $arg = func_get_args();
        self::ubLog(self::LOG_DEBUG, $arg );
    }
    
    /**
     * trace                   TRACE日志
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function trace(){
        $arg = func_get_args();
        self::ubLog(self::LOG_TRACE, $arg);
    }
    
    /**
     * notice                  NOTICE日志,一般一次请求只打一条 
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function notice(){
        $arg = func_get_args();
        self::ubLog(self::LOG_NOTICE, $arg );
    }
    
    /**
     * monitor                  MONITOR日志,主要用于监控
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function monitor(){
        $arg = func_get_args();
        self::ubLog(self::LOG_MONITOR, $arg );
    }
    
    /**
     * warning                  WANRING日志 
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function warning(){
        $arg = func_get_args();
        self::ubLog(self::LOG_WARNING, $arg );
    }
    
    /**
     * fatal            FATAL日志,会同时打出MONITOR日志的标识 
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function fatal(){
        $arg = func_get_args();
        self::ubLog(self::LOG_FATAL, $arg );
    }
    
    /**
     * pushNotice       压入NOTICE日志,和UB_LOG_XXX接受的参数相同(不同于ub_log同名函数))  
     * @param string $fmt      格式字符串
     * @param mixed  $arg      data
     * @return void
     */
    public static function pushNotice(){
        $arr = func_get_args();
    
        $pid = posix_getpid();
    
        if (! empty(self::$logId[$pid])) {
            $libLogObj = self::$logId[$pid];
            $format = $arr[0];
            /* shift $type and $format, arr_data left */
            array_shift($arr);
            $libLogObj->push_notice($format, $arr);
        } else {
            /* nothing to do */
        }
    }
    
    /**
     * clearNotice       清除目前的NOTICE日志,每次调用UB_LOG_NOTICE都会调用本函数
     * @return void
     */
    public static function clearNotice(){
        $pid = posix_getpid();
    
        if (! empty(self::$logId[$pid])) {
            $libLogObj = self::$logId[$pid];
            $libLogObj->clear_notice();
        } else {
            /* nothing to do */
        }
    }
    
    /**
     * addBasic       添加日志的基本信息,字段可以参考 BASIC_FIELD 
     * @param mixed $arr_basic 基本信息的数组 
     * @return void
     */
    public static function addBasic($arr_basic){
        $pid = posix_getpid();
    
        if (! empty(self::$logId[$pid])) {
            $libLogObj = self::$logId[$pid];
            $libLogObj->add_basicinfo($arr_basic);
        } else {
            /* nothing to do */
        }
    }
    
    public static function exception($e){
        self::warning('caught exception [%s]', $e);
    }
    
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
