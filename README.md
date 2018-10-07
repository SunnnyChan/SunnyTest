# hikari-test
> by sunnnychan@gmail.com

```md
API 测试框架
```

## 特性
```md
1. 支持 HTTP API 接口的集成测试、系统测试；
2. 支持 Case 书写，Case 数据使用 统一的模式，由 PHP 的数组结构来表述， 非常清晰、易读；
3. 支持 Case 的管理，方便 回归测试；
4. 支持 Case 的批量执行，也支持 单个Case 执行；
5. 支持 动态配置 Mock 功能，形成测试闭环，Mock功能封装在 测试Case中，便于统一管理；
6. 支持 Case 执行结果的汇总，输出报告。
```

## 安装
```md
git clone https://github.com/SunnnyChan/sunny-test.git
```
> 注意： 需要PHP执行环境

## 配置
* 被测环境配置
```md
具体参考 conf/testmod/ExpCenter.conf.php
复制该文件，修改文件名，按照实际被测 服务的 部署来修改配置。
```
* mock 配置
```md
注意 ： cat conf/MockConf.php 配置文件 主要是配置Mock使用的端口，一般情况下 无需修改。
```

## 实例
### Case 创建
> 参考 /cases/TestExp.case.php

```php
<?php
class TestExp {
	static $TEST_MOD = 'ExpCenter';
	static $HTTP_API = 'ExperStrategyCenter/api/judgeExperStrategy';
    static $CALL_API_BEFORE = array(
        'api' => 'ExperStrategyCenter/api/updateexperinfo',
        'req' => array(
            'update' => array(
                'group_test_uri' => array (
                    'exp_test_uri' => array (
                            'end_time' => '',
                            'start_time' => '2015-08-25 15:46:59',
                            'rule_express' => 'rule01&(rule02|inner_ip_01|inner_ip_02)',
                            'exp_content' => '/api/wap_direct/0',
                            'experiment_strategy_rules' => array (
                                'rule01' => array (
                                    'rule_dimension' => '7',
                                    'rule_style' => '10',
                                    'rule_content' => '/api/wap_direct/0',
                                ),
                                'rule02' => array (
                                    'rule_dimension' => '8',
                                    'rule_style' => '1',
                                    'rule_content' => '30/100',
                                ),
                            ),
                        ),
                    ),
            ),
        ),
    );
	static $DB_INIT = array(
	);
	static $DB_WRITE = array(
	);
	static $REQUEST_PACK = array(
        'groupid' => 'all',
        'cuid' => 'user_dkflsdkf',
        'client_ip' => '10.129.0.10',
        'ua' => 'Mozilla/5.0 (Linux; U; Android 4.2.2; zh-cn; InFocus M310 Build/JDQ39) ...',
        'uri_for_exp' => '/api/wap_direct/0',
	);
	static $MOCK_RETURN = array(
	);
	static $RESPONSE_PACK = array(
		'DATA_FORMAT' => 'json',
		'ret' => 0,
		'data'=> array(
            'group_test_uri_preg' => array(
                'exp_code' => 'exp_test_uri_preg',
                'exp_content' => '/api/wap_direct/0',
            ),
		),
		'msg' => 'AB test judge OK',
	);
	static $DB_CHECK = array(
	);
    static $CALL_API_AFTER = array(
        'api' => 'ExperStrategyCenter/api/updateexperinfo',
        'req' => array(
            'delete' => array(
                'group_test_uri_preg',
            ),
        ),
    );
}

?>
```

> 从数组的名称就能 分辨，Case的每个部分 表述的是 哪个 执行步骤的 数据。

### Case 执行
* 执行单个 Case 文件
```sh
sh run.sh cases/TestExp.case.php
```

* 按目录批量执行
```sh
sh run.sh cases/
```
> 会扫描 所有子目录，尝试加载 case.php 结尾的文件。

### 输出
```md
CASE FILE   : cases/TestExp.case.php
RUN RESULT  : Fail
ERROE STEP  : connect to server error :Connection refused

*********************************
Case Run Statistics
Sum  : 1
Pass : 0
Fail : 1
*********************************
```

## Todo
```md
1. Case 的并行执行，要考虑 API 之间如果存在逻辑上的时序关系，该怎样解耦？
2. Case 的创建 工作量变较大，实际上API测试的各个 Case 之间 基本是个别 参数的变化，如果简化这种重复？
```