<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Argument.php
 * @date 2015/06/30
 * @author sunnnychan@gmail.com
 * @brief 
 *
 **/

class Argument {
    private $argv = '';
    private $argc = '';

    public $concurrFlag = false;
    
    public function __construct($argv, $argc){
        $this->argv = $argv;
        $this->argc = $argc;
    }
    /** 解析命令选项
     *  @return fasle 解析失败 | ture 执行指令成功 | array case file 列表
     * */
    public function parse(){
        $caseFileArr = array();
        unset($this->argv[0]);
        foreach ($this->argv as $key => $value){
            switch ($value){
                case '-h' :
                    $this->printHelp();
                    return true;
                case '-c' :
                    $this->concurrFlag = true;
                default:
                    $caseFileArr[] = $value;
            }
        }
        return $caseFileArr;
    }

    public function printHelp(){
        print("Usage:\n");
        print("php MainCtrl.php [-h] [case files]\n");
        print("     -h print this usage.\n");
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
