<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Testcase.php
 * @date 2015/06/30
 * @author chenguang02@baidu.com
 * @brief
 *
 **/

class Case_Testcase {
    // case 文件路径
    private $caseFile;
    // case 数据
    private $arrCaseData;

    // check 数据时，保存check 失败的数据
    private $checkErrorArr = '';
    // Http Client
    private $httpClient;
    private $confObj;

    // case 中定义数据的各个部分
    private static $CASE_SECTION = array(
        'TEST_MOD_SEC' => 'TEST_MOD',
        'HTTP_API_SEC' => 'HTTP_API',
        'CONF_FILE_SEC' => 'CONF_FILE',
        'CALL_API_BEFORE_SEC' => 'CALL_API_BEFORE',
        'DB_INIT_SEC' => 'DB_INIT',
        'DB_WRITE_SEC' => 'DB_WRITE',
        'REQUEST_PACK_SEC' => 'REQUEST_PACK',
        'MOCK_RETURN_SEC' => 'MOCK_RETURN',
        'RESPONSE_PACK_SEC' => 'RESPONSE_PACK',
        'DB_CHECK_SEC' => 'DB_CHECK',
        'CALL_API_AFTER_SEC' => 'CALL_API_AFTER',
    );

    public function __construct($caseFile){
       $this->caseFile = $caseFile;
       $this->arrCaseData = $this->loadCaseFile();
       $this->httpClient = '';
       $this->confObj = ConfLoader::getInstance();
    }

    /** require case file, get case data
     *  @return  false 失败
     *           array case数据
     * */
    private function loadCaseFile(){
        if (is_file($this->caseFile)){
            require_once($this->caseFile);
            $caseClassName = basename($this->caseFile, '.case.php');
            if (class_exists($caseClassName)){
                $caseClassReflect = new ReflectionClass($caseClassName);
                return $caseClassReflect->getStaticProperties();
            } else {
                Lib_Log::fatal( "Error: case file \"" . $this->caseFile . "\" load failed." . "\n" .
                     "       may be class name not equal with file name or case file name not end with \"case.php\".");
               return false;
            }
        } else {
            Lib_Log::fatal("Error: case file \"" . $this->caseFile . "\" can not find.");
            return false;
        }
    }

    /** 从Case中获取 测试的模块名，用户获取conf文件 和 作为日志文件名
     *  @return true | false
     * */
    public function getTestmod(){
        if ((false !== $this->arrCaseData) && ! empty($this->arrCaseData['TEST_MOD'])){
            return $this->arrCaseData['TEST_MOD'];
        } else {
            Lib_Log::fatal( "Error: case file \"" . $this->caseFile . "\" load TEST_MOD failed.");
            return false;
        }
    }
    /** 运行Case
     *  @return false|true
     * */
    public function runCase(){
        try {
            Lib_Log::notice("begin to run case test module:%s case file:%s", $this->getTestmod(), $this->caseFile);
            // 加载测试数据，类初始化时已加载，这里只需判断是否加载成功
            Lib_Log::trace("[ Run Case Step ]: load case data.");
            if (false === $this->arrCaseData){
                $errCode = Def_ErrorNo::ERROR_LOAD_CASE_DATA_FAILED;
                throw new Exception(Def_ErrorNo::$err_info[$errCode], $errCode);
            }
            // 设置测试环境配置文件
            if (isset($this->arrCaseData['CONF_FILE'])){
                Lib_Log::trace("[ Run Case Step ]: set conf file.");
                if (false === $this->setTestmodConfFile($this->confObj->testmodConf['HOME'])){
                    Lib_Log::warning("set conf file failed, will skip this step.");
                }
            }
            // 调API 预置环境
            if (isset($this->arrCaseData['CALL_API_BEFORE'])){
                Lib_Log::trace("[ Run Case Step ]: do API call before.");
                if (false === $this->callApiBefore()){
                    Lib_Log::warning("do API call before failed, will skip this step.");
                }
            }
            // 数据库初始化
            if (isset($this->arrCaseData['DB_INIT'])){
                Lib_Log::trace("[ Run Case Step ]: do database init.");
            }
            // 预置数据库数据
            if (isset($this->arrCaseData['DB_WRITE'])){
                Lib_Log::trace("[ Run Case Step ]: do database write.");
            }
            // 发送请求
            Lib_Log::trace("[ Run Case Step ]: send request.");
            if (! $this->callTestmod($this->confObj->testmodConf, $this->arrCaseData['HTTP_API'], $this->arrCaseData['REQUEST_PACK'])){
                throw new Exception('send request failed', Def_ErrorNo::ERROR_RUN_CASE_SEND_REQ);
            }
            // 处理桩模块
            Lib_Log::trace("[ Run Case Step ]: set mock data.");
            if (is_array($this->arrCaseData['MOCK_RETURN']) && ! empty($this->arrCaseData['MOCK_RETURN'])){
                $this->handleMock();
            } else {
                Lib_Log::warning("mock return data may not set, will skip this step.");
            }
            // 接受响应
            Lib_Log::trace("[ Run Case Step ]: receive response.");
            $response = $this->recvResponse();
            if (false === $response){
                throw new Exception('receive response failed', Def_ErrorNo::ERROR_RUN_CASE_RECV_RES);
            } else {
                Lib_Log::trace("get response from test module :%s", var_export($response, true));
            }
            // 校验响应
            Lib_Log::trace("[ Run Case Step ]: check response.");
            $checkResponseRet = $this->checkResponse($response);
            // 调用API清理数据（先清理数据，再检查数据）
            if (isset($this->arrCaseData['CALL_API_AFTER'])){
                Lib_Log::trace("[ Run Case Step ]: do API call after to clear test data.");
                if (false === $this->callApiAfter()){
                    Lib_Log::warning("do API call failed, will skip this step.");
                }
            }
            if (false === $checkResponseRet){
                throw new Exception('check response failed', Def_ErrorNo::ERROR_RUN_CASE_CHECK_RES);
            }
            // 校验数据库数据
            Lib_Log::trace("[ Run Case Step ]: do database check.");
            // 打印 报告
            Case_Reporter::termReport($this->caseFile, true);
            Lib_Log::trace("run case successfully.");
        } catch (Exception $e){
            Lib_Log::fatal("run case failed faild. error_no:%s error_msg:", $e->getCode(), $e->getMessage());
            Case_Reporter::termReport($this->caseFile, false, $e->getMessage());
            return false;
        }
        return true;
    }
    /** 加载并校验Case文件的各个部分的数据
     *  @return true | false
     * */
    public function loadCaseData(){
        if (! isset($this->arrCaseData['HTTP_API'])){
            Lib_Log::fatal("\"%s\" not set in case, if test API use http protocol, this must set.", 'HTTP_API');
            return false;
        }
        if (! isset($this->arrCaseData['CONF_FILE'])){
            Lib_Log::trace("\"%s\" not set in case, if you should change some conf file of test env before test, you need to set this section.",
                'CONF_FILE');
        }
        if (! isset($this->arrCaseData['CALL_API_BEFORE'])){
            Lib_Log::trace("\"%s\" not set in case, if you should call some api to set enviroment, you need to set this section.",
                'CALL_API_BEFORE');
        }
        if (! isset($this->arrCaseData['DB_INIT'])){
            Lib_Log::trace("\"%s\" not set in case.", 'DB_INIT');
        }
        if (! isset($this->arrCaseData['DB_WRITE'])){
            Lib_Log::trace("\"%s\" not set in case.", 'DB_WRITE');
        }
        if (! isset($this->arrCaseData['REQUEST_PACK'])){
            Lib_Log::fatal("\"%s\" not set in case. this case section must set.", 'REQUEST_PACK');
            return false;
        }
        if (! isset($this->arrCaseData['MOCK_RETURN'])){
            Lib_Log::trace("\"%s\" not set in case.", 'MOCK_RETURN');
        }
        if (! isset($this->arrCaseData['RESPONSE_PACK'])){
            Lib_Log::fatal("\"%s\" not set in case. this case section must set.", 'RESPONSE_PACK');
            return false;
        }
        if (! isset($this->arrCaseData['DB_CHECK'])){
            Lib_Log::trace("\"%s\" not set in case.", 'DB_CHECK');
        }
    }
    /** 设置配置文件
     *  @return false|true
     * */
    private function setTestmodConfFile($testmodHome){
        if (is_array($this->arrCaseData['CONF_FILE']) && ! empty($this->arrCaseData['CONF_FILE'])){
            foreach ($this->arrCaseData['CONF_FILE'] as $confFile) {
                if (is_array($confFile) && ! empty($confFile)){
                    if (isset($confFile['ENV_CONF_PATH'])){
                        $evnConfPath = $testmodHome . '/' . $confFile['ENV_CONF_PATH'];
                    } else {
                        Lib_Log::warning('test module conf file path not set in case, will skip conf file setup.');
                        return false;
                    }
                    if (isset($confFile['CASE_CONF_PATH'])){
                        $caseConfPath = dirname(realpath($this->caseFile)) . '/' . $confFile['CASE_CONF_PATH'];
                    } else {
                        Lib_Log::warning('case conf file path not set in case, will skip conf file setup.');
                        return false;
                    }
                    if (Lib_File::copyFile($evnConfPath, $caseConfPath)){
                        Lib_Log::trace('set conf file OK. from:%s to:%s', $caseConfPath, $evnConfPath);
                        return true;
                    } else {
                        Lib_Log::warning('set conf file failed.');
                        return false;
                    }
                } else {
                    Lib_Log::warning("\"CONF_FILE\" section in case file, format error, will not do file copy.");
                    return false;
                }
            }
        } else {
            Lib_Log::trace("\"CONF_FILE\" section not set in case, will skip setting conf file setup.");
            return true;
        }
    }
    /** 调API 预置环境
     *
     */
    private function callApiBefore(){
        $arrReq = array();
        if (! empty($this->arrCaseData['CALL_API_BEFORE']['req']['update'])){
            $reqJson = json_encode($this->arrCaseData['CALL_API_BEFORE']['req']['update']);
            $arrReq['update'] = $reqJson;
        }
        if (! empty($this->arrCaseData['CALL_API_BEFORE']['req']['delete'])){
            $reqJson = json_encode($this->arrCaseData['CALL_API_BEFORE']['req']['delete']);
            $arrReq['delete'] = $reqJson;
        }
        Lib_Log::trace('call api :%s  request:%s', $this->arrCaseData['CALL_API_BEFORE']['api'], $reqJson);
        if (! $this->callTestmod($this->confObj->testmodConf, $this->arrCaseData['CALL_API_BEFORE']['api'], $arrReq)){
            Lib_Log::warning("call API before faild, send pack failed.");
            return false;
        }
        $ret = $this->httpClient->getResponse();
        if (! $ret){
            Lib_Log::warning("call API before faild, recevie pack failed.");
            return false;
        }
        Lib_Log::trace('call return : %s', print_r($ret, true));
        return true;
    }
    /** 预置环境，清理数据库
     *  @return false|true
     * */
    private function doDbInit(){
        return false;
    }

    /** 预置环境，准备数据
     *  @return false|true
     * */
    private function doDbWrite(){
        return false;
    }

    /** 处理桩
     *  @return false|true
     * */
    private function handleMock(){
        $httpMockObj = Mock_HttpMocker::getIns();
        foreach($this->arrCaseData['MOCK_RETURN'] as $mock => $data){
            Lib_Log::debug(sprintf("mock data:%s", print_r($data, true)));
            try {
                $httpMockObj->accept();
            } catch (Exception $e) {
                Lib_Log::fatal('handle mock data failed. error_no:%s error_msg:%s', $e->getCode(), $e->getMessage());
                return false;
            }
        }
        return false;
    }

    /** 调测试模块接口
     *  @return false|true
     * */
    private function callTestmod($testmodArr, $api, $Req){
        if ('http' === $testmodArr['PROTOCOL']){
            $callAdd =  'http://' . $testmodArr['HOST'] . ':' .$testmodArr['PORT'] . '/' . $api;
            Lib_Log::trace('do http call : %s', $callAdd);
            Lib_Log::trace('request data : %s', print_r($Req, true));
            $this->httpClient  = new Rpc_HttpClient($testmodArr, $api, true, HttpDef::HTTP_V11);
            if (! $this->httpClient->sendPack($Req)){
               return false;
            }
        } else {
            Lib_Log::fatal('can not dispose protocol:%s', $testmodArr['PROTOCOL']);
            return false;
        }
        return true;
    }
    /** 接收测试的响应
     *
     * */
    private function recvResponse(){
        return $this->httpClient->getResponse();
    }
    /** 校验响应
     *  @return false|true
     * */
    private function checkResponse($testmodRes){
        Lib_Log::debug("case file response data :%s", var_export($this->arrCaseData['RESPONSE_PACK'], true));
        if ('json' === $this->arrCaseData['RESPONSE_PACK']['DATA_FORMAT']){
            unset($this->arrCaseData['RESPONSE_PACK']['DATA_FORMAT']);
            $testmodResArr = json_decode($testmodRes, true);
            if (is_array($testmodResArr)){
                Lib_Log::debug('test module response data after json decode: %s', var_export($testmodResArr, true));
                $caseAssertionObj = new Case_Assertion();
                $this->checkErrorArr = $caseAssertionObj->checkArrayInc($testmodResArr, $this->arrCaseData['RESPONSE_PACK']);
                if (false === $caseAssertionObj->checkRes){
                    Lib_Log::fatal('check response data failed.');
                }
                return $caseAssertionObj->checkRes;
            } else {
                Lib_Log::fatal('test module response data format error. not a json');
                return false;
            }
        } else {
            Lib_Log::fatal('case response data format can not dispose.');
            return false;
        }
    }
    /** 检验数据库数据
     *  @return false|true
     * */
    private function doDbCheck(){
        return false;
    }
    /** 调API 清理环境
     *
     */
    private function callApiAfter(){
        $arrReq = array();
        if (! empty($this->arrCaseData['CALL_API_AFTER']['req']['delete'])){
            $arrReq['delete']  = json_encode($this->arrCaseData['CALL_API_AFTER']['req']['delete']);
        }
        Lib_Log::trace('call api :%s  request:%s', $this->arrCaseData['CALL_API_AFTER']['api'], $arrReq['delete']);
        if (! $this->callTestmod($this->confObj->testmodConf, $this->arrCaseData['CALL_API_AFTER']['api'], $arrReq)){
            Lib_Log::warning("call API before faild, send pack failed.");
            return false;
        }
        $ret = $this->httpClient->getResponse();
        if (! $ret){
            Lib_Log::warning("call API before faild, recevie pack failed.");
            return false;
        }
        Lib_Log::trace('call return : %s', print_r($ret, true));
        return true;
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
