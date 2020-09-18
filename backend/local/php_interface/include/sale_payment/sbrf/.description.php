<?php

$data = array(
	'NAME' => 'Sberbank',
	'SORT' => 500,
	'CODES' => array(
		'SBRF_NAME' => array(
			'NAME' => 'Мерчант',
			'SORT' => 50,
			'GROUP' => 'GENERAL_SETTINGS',
		),
		'SBRF_LOGIN' => array(
			'NAME' => 'Логин',
			'SORT' => 100,
			'GROUP' => 'GENERAL_SETTINGS',
		),
		'SBRF_PASSWORD' => array(
			'NAME' => 'Пароль',
			'SORT' => 200,
			'GROUP' => 'GENERAL_SETTINGS',
		),
		"SBRF_RESULT_URL" => array(
			'NAME' => 'Страница результата оплаты',
			'SORT' => 300,
			'GROUP' => 'GENERAL_SETTINGS',
		),
		"SBRF_SUCCESS_URL" => array(
			'NAME' => 'Страница успешной оплаты',
			'SORT' => 350,
			'GROUP' => 'GENERAL_SETTINGS',
		),
		"SBRF_FAIL_URL" => array(
			'NAME' => 'Страница неудачной оплаты',
			'SORT' => 400,
			'GROUP' => 'GENERAL_SETTINGS',
		),
		'PAYMENT_ID' => array(
			'NAME' => 'Номер оплаты',
			'SORT' => 500,
			'GROUP' => 'PAYMENT',
			'DEFAULT' => array(
				'PROVIDER_VALUE' => 'ID',
				'PROVIDER_KEY' => 'PAYMENT'
			)
		),
		'PAYMENT_SHOULD_PAY' => array(
			'NAME' => 'Сумма оплаты',
			'SORT' => 600,
			'GROUP' => 'PAYMENT',
			'DEFAULT' => array(
				'PROVIDER_VALUE' => 'SUM',
				'PROVIDER_KEY' => 'PAYMENT'
			)
		),
		'PAYMENT_CURRENCY' => array(
			'NAME' => 'Валюта счета',
			'SORT' => 700,
			'GROUP' => 'PAYMENT',
			'DEFAULT' => array(
				'PROVIDER_VALUE' => 'CURRENCY',
				'PROVIDER_KEY' => 'PAYMENT'
			)
		),
		'PS_IS_TEST' => array(
			'NAME' => 'Тестовый режим',
			'SORT' => 1300,
			'GROUP' => 'GENERAL_SETTINGS',
			"INPUT" => array(
				'TYPE' => 'Y/N'
			)
		),
	)
);