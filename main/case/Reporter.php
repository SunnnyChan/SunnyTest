<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Reporter.php
 * @date 2015/07/01
 * @author sunnnychan@gmail.com
 * @brief
 *
 **/

class Case_Reporter {
    /** 打印到终端屏幕的case 执行报告
     *  @param case 执行的case文件
     *  @param res  执行结果 pass | fail
     *  @param errorStep 如果执行错误，是在哪一步发生的错误
     *  @param checkErrorArr 如果在check数据发生错误，错误信息
     *  @return  always true
     * */
    public static function termReport($case, $res, $errorStep = '', $checkErrorArr = NULL){
        $colorObj = new Lib_Color();

        $caseStr = $colorObj->getColoredString($case, 'blue', '');
        echo sprintf($colorObj->getColoredString("CASE FILE", 'green', '') . "   : %s\n", $caseStr);
        $resStr =  $res ? 'Pass' : $colorObj->getColoredString('Fail', 'red', '');
        echo sprintf("RUN RESULT  : %s\n", $resStr);
        if ('' !== $errorStep){
            echo "ERROE STEP  : " . $errorStep . "\n";
        }
        if (is_array($checkErrorArr)){
            $keyStr = $colorObj->getColoredString('[Key]', 'green', '');
            $wantedValueStr = $colorObj->getColoredString('[Wanted_Value]', 'green', '');
            $returnValueStr = $colorObj->getColoredString('[Retrun_Value]', 'green', '');
            echo "CHECK ERROR : \n";
            foreach ($checkErrorArr as $key => $value){
                if (is_string($value['WANTED_VALUE'])){
                    $value['WANTED_VALUE'] = "\"" . $value['WANTED_VALUE'] . "\"";
                }
                if (is_string($value['RETURN_VALUE'])){
                    $value['RETURN_VALUE'] = "\"" . $value['RETURN_VALUE'] . "\"";
                }
                echo sprintf("%s \"%s\" %s %s %s %s\n",
                    $keyStr, $key , $wantedValueStr, $value['WANTED_VALUE'], $returnValueStr, $value['RETURN_VALUE']);
            }
        }
        echo "\n";
        return true;
    }

    /** 总的统计报告
     *  @param sum 执行case总数
     *  @param pass 执行通过数
     *  @param fail 执行失败数
     */
    public static function statTermReport($sum, $pass, $fail){
        $colorObj = new Lib_Color();
        echo $colorObj->getColoredString( "********************************* \n", 'brown', '');
        echo $colorObj->getColoredString("Case Run Statistics \n", 'green', '');
        echo sprintf("Sum  : %s\nPass : %s\nFail : %s\n", $sum, $pass, $fail);
        echo $colorObj->getColoredString( "********************************* \n", 'brown', '');
    }

    /** 记录于report 目录中的执行结果报告
     */
    public static function textReport(){

    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
