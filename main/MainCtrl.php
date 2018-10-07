<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file MainCtrl.php
 * @date 2015/06/18
 * @author sunnnychan@gmail.com
 * @brief
 *
 **/

class MainCtrl {
    //测试框架的名称，框架的启动日志以它命名
    private $testFrame = 'sunnytest';
    //Mock的配置文件类名
    private $mockConf = 'MockConf';
    //HTTP 协议 的mocker 名
    private $httpMockName = 'http mocker';
    //frame 目录，用于框架类的加载
    private static $autoloadDir = '';

    //命令行参数，传递给参数解析类处理
    private $testArgc = '';
    private $testArgv = '';
    //保存所有获取到的Case 文件
    private $caseFileArr = '';
    //保存case的数目，用于最后的执行统计
    private $caseCount = 0;
    private $passCount = 0;

    /**
     * @param argv 脚本的命令行参数数组
     * @param argc 脚本的命令参数个数
     */
    public function __construct($argv, $argc){
        self::$autoloadDir = realpath(dirname(__FILE__) . '/frame');
        spl_autoload_register(array('MainCtrl', 'loadClass'));

        $this->testArgv = $argv;
        $this->testArgc = $argc;
    }

    /** 整个框架的入口函数，也是整个主流程的控制函数
     *  @return true|false
     */
    public function __main(){
        InitFrame::init();
        //记录框架启动的日志
        $frameLogId = time() . '_' . posix_getpid();
        Lib_Log::logInit(LOG_PATH, $this->testFrame, 16, array("logid" => $frameLogId));
        Lib_log::notice("begin to init start test frame.");
        try {
            //解析命令行选项
            Lib_log::trace("parse arguments.");
            $argumentObj = new Argument($this->testArgv, $this->testArgc);
            //获取Case文件名
            $this->caseFileArr = $argumentObj->parse();
            if (is_array($this->caseFileArr) && ! empty($this->caseFileArr)){
                Lib_Log::trace(sprintf("this time, will run these cases :%s", print_r($this->caseFileArr, true)));
            } else if (true === $this->caseFileArr){
                return true;
            } else {
                Lib_Log::fatal(sprintf("parse arguments failed."));
                throw new Exception('parse arguments failed.', Def_ErrorNo::ERROR_INIT_FRAME_PARSE_ARG);
            }
            //启动 Mock
            Lib_log::trace("start mockers.");
            $mockConfObj = ConfLoader::getInstance();
            $mockConfObj->loadMockConf($this->mockConf);
            $httpMockObj = Mock_HttpMocker::getIns();
            $httpMockObj->startMock($mockConfObj->httpMockConf, $this->httpMockName);
        } catch(Exception $e){
            Lib_Log::fatal("init frame faild. error_no:%s error_msg:", $e->getCode(), $e->getMessage());
            echo sprintf("Error: start test frame faild. error_no:%s error_msg:%s \n", $e->getCode(), $e->getMessage());
            return  $e->getCode();
        }
        Lib_log::notice("start test frame successfully. After running cases, please check test module log for result.");

        // 执行Case
        if ($argumentObj->concurrFlag){
            $this->concurrExec();
        } else {
            $this->serialExec();
        }
        return true;
    }

    /** 串行执行
     *
     * */
    private function serialExec(){
        foreach ($this->caseFileArr as $caseFile){
            $this->caseCount ++;
            if ($this->execCase($caseFile)){
                $this->passCount ++;
            }
        }
        Case_Reporter::statTermReport($this->caseCount, $this->passCount, $this->caseCount - $this->passCount);
    }

    /** 多进程执行Case
     *
     * */
    private function concurrExec(){
        $libBlockObj = new Lib_Block();
        $libBlockObj->write('0');

        $pidArr =  array();
        foreach ($this->caseFileArr as $caseFile){
            $this->caseCount ++;
            $pid = pcntl_fork();
            $pidArr[] = $pid;
            if ($pid == -1){
                echo ("Error: fork sub process failed.");
            } else if ($pid == 0) {
                //子进程执行完Case直接结束
                $subProRet = $this->execCase($caseFile);
                if ($subProRet){
                    $semId = sem_get($libBlockObj->id);
                    sem_acquire($semId);
                    $currPassCnt = intval($libBlockObj->read()) + 1;
                    $libBlockObj->write(strval($currPassCnt));
                    sem_release($semId);
                    sem_remove($semId);
                }
                return $subProRet;
            }
        }
        foreach ($pidArr as $pid){
            pcntl_waitpid($pid, $status, WUNTRACED);
        }
        $this->passCount = $libBlockObj->read();
        $libBlockObj->delete();
        Case_Reporter::statTermReport($this->caseCount, $this->passCount, $this->caseCount - $this->passCount);
    }

    /** Case 执行的方法
     * @param caseFile case文件路径
     * @return fasle | true case 执行结果
     */
    private function execCase($caseFile){
        $caseTestcaseObj = new Case_TestCase($caseFile);
        $testModuleName = $caseTestcaseObj->getTestmod();
        // 测试模块名加载成功，获取测试配置和初始化Log
        if (false !== $testModuleName ){
            $caseConfObj = ConfLoader::getInstance();
            $caseConfObj->loadTestmodConf($testModuleName);
            //各个子进程拥有自己的log id
            $logId = time() . '_' . posix_getpid();
            Lib_Log::logInit(LOG_PATH, $testModuleName, $caseConfObj->logConf['LOG_LEVEL'], array("logid" => $logId));
        }
        return $caseTestcaseObj->runCase();
    }

    /** frame 目录下一些框架类的自动加载函数
     * @param className 类名
     * @return NULL
     */
    private static function loadClass($className){
        $classFile = self::$autoloadDir . '/' . $className . '.php';
        if (file_exists($classFile)){
            require_once($classFile);
        }
    }
}//class

$mainCtrlObj = new MainCtrl($argv, $argc);
$mainCtrlObj->__main();

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
