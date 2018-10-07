<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file File.php
 * @date 2015/07/01
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class Lib_File {

    /* 文件拷贝
     * @param $destFile 目标文件地址
     * @param $scrFile  源文件地址
     * @return true | false
     * */
    public static function copyFile($destFile, $scrFile){
        $confDir = dirname($destFile);
        if (! file_exists($confDir)){
            Lib_Log::fatal('destination directroy:%s not exists, copy file failed.', $copyFile); 
            return false;
        }
        if (! is_file($scrFile)){
            Lib_Log::fatal('scource file:%s not exists, copy file failed.', $scrFile); 
            return false;
        }
        if (! copy($scrFile, $destFile)){
            Lib_Log::fatal('error occur when copy file.', $scrFile);
            return false;
        }
        return true;
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
