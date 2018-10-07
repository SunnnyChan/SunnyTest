#!/bin/bash -

SUNNYTEST_HOME=$(cd "$(dirname $0)/.."; pwd)
MAIN_CTRL_FILE=${SUNNYTEST_HOME}'/main/MainCtrl.php'
# alias php=${SUNNYTEST_HOME}'/php/bin/php'

php ${MAIN_CTRL_FILE} $@
