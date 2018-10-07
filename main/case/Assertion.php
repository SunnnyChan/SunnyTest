<?php
/***************************************************************************
 *
 * Copyright (c) 2015  Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Assertion.php
 * @date 2015/07/01
 * @author sunnnychan@gmail.com
 * @brief 
 *
 **/

class Case_Assertion {
    public $checkRes = true;
    public $errorArr = array();
    /** 校验数组包含，实际上check case 中预期的返回，是否和实际的返回一致
     *  @param parentArr  测试模块实际返回数据
     *  @param subArr     case 中预期的返回数据
     *  @return Null
     * */
    public function checkArrayInc($parentArr, $subArr){
        foreach($subArr as $key => $value){
            if (! isset($parentArr[$key])){
                $this->errorArr[$key] =  array(
                    'WANTED_VALUE' => $value,
                );
                $this->checkRes = false;
                Lib_Log::fatal('key:%s not set in response data.', $key);
                continue;
            }
            if (is_array($value)){
                Case_Assertion::checkArrayInc($parentArr[$key], $value);  
            } else {
                if ($value !== $parentArr[$key]){
                    $this->errorArr[$key] =  array(
                        'WANTED_VALUE' => $value,
                        'RETURN_VALUE' => $parentArr[$key],
                    );
                    $this->checkRes = false;
                    Lib_Log::fatal('Key:%s Value_Wanted:%s Value_Return:%s', $key, $value, $parentArr[$key]);
                    continue;
                }
            } 
        }
        return $this->errorArr;
    }

}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
