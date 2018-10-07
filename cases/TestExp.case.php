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
                                'inner_ip_01' => array (
                                    'rule_dimension' => '1',
                                    'rule_style' => '6',
                                    'rule_content' => '10.129.0.1/32,10.129.0.2/32',
                                ),
                                'inner_ip_02' => array (
                                    'rule_dimension' => '1',
                                    'rule_style' => '6',
                                    'rule_content' => '10.129.0.3/32,10.129.0.2/32',
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
		'bduss' => 'BoYURXSzlsZmhyVFNvSU0zLUE1VFhjaFVqeGlrMkxQMGV2U2tPSlhaY3RVZVZSQVFBQUFBJCQAAAAAAAAAAAoawA1~Rr4TeWFuZ3Jhbl9iZAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAYIArMAAAALDGZXsAAAAA6p5DAAAAAAAxMC4yNi4yMi0D-FAtA~hQVl',
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
