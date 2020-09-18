<?php

namespace Olympia\Fssmo\Sale\Payments;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale\Cashbox\Check;
use Bitrix\Sale\Cashbox\Internals\CashboxCheckTable;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Internals\PaymentTable;
use Olympia\Fssmo\Exception;

class Sberbank extends Base
{
	protected $_internalId = 4;

	public function createOrder ($paymentId, $paymentData = [])
	{
		$payment = PaymentTable::getRow([
			'filter' => ['=ID' => (int) $paymentId]
		]);

		if (!$payment)
			throw new Exception('payment not found');

		$this->setInternalId($payment['PAY_SYSTEM_ID']);

		$order = OrderTable::getRow([
			'select' => ['ID', 'USER_EMAIL' => 'USER.EMAIL'],
			'filter' => ['=ID' => $payment['ORDER_ID']]
		]);

		$bundle = [
			'orderCreationDate' => time() * 1000,
			'customerDetails' => [
				'email' => $order['USER_EMAIL']
			],
			'cartItems' => [
				'items' => []
			]
		];

		$data = [];
		$totalPrice = 0;

		$items = BasketTable::getList([
			'select' => ['ID', 'NAME', 'PRODUCT_ID', 'QUANTITY', 'PRICE'],
			'filter' => ['=ORDER_ID' => $payment['ORDER_ID'], '>PRICE' => 0]
		]);

		foreach ($items as $item)
		{
			$data[] = $item;

			$totalPrice += $item['PRICE'] * $item['QUANTITY'];
		}

		$payment['SUM'] = ceil($payment['SUM']);

		$priceBuffer = $payment['SUM'];
		$totalItems = count($data);

		foreach ($data as $i => $item)
		{
			if ($totalItems > 1)
			{
				$item['PRICE'] = ceil($item['PRICE'] * (ceil($paymentData['PAYMENT_SHOULD_PAY']) / $totalPrice));

				if ($totalItems == ($i + 1))
					$item['PRICE'] = $priceBuffer / $item['QUANTITY'];
				else
					$priceBuffer -= $item['PRICE'] * $item['QUANTITY'];
			}
			else
				$item['PRICE'] = $payment['SUM'] / $item['QUANTITY'];

			$bundle['cartItems']['items'][] = [
				'positionId' => (int) $item['ID'],
				'name' => $item['NAME'],
				'quantity' => [
					'value' => (int) $item['QUANTITY'],
					'measure' => 'штук'
				],
				'itemCode' => (int) $item['PRODUCT_ID'],
				'itemAmount' => (int) (round($item['PRICE'] * 100) * $item['QUANTITY']),
				'itemPrice' => (int) round($item['PRICE'] * 100),
				'tax' => [
					'taxType' => 0
				]
			];
		}

		$result = $this->sendRequest('register.do', [
			'userName'		=> $this->getProp('SBRF_LOGIN'),
			'password'		=> $this->getProp('SBRF_PASSWORD'),
			'orderNumber'	=> (int) $payment['ID'],
			'amount'		=> (int) (floatval($payment['SUM']) * 100),
			'currency'		=> $payment['CURRENCY'] == 'RUB' ? 643 : 0,
			'returnUrl'		=> $this->getProp('SBRF_RESULT_URL'),
			'failUrl'		=> $this->getProp('SBRF_FAIL_URL'),
			'language'		=> mb_strtoupper(LANGUAGE_ID),
			'orderBundle'	=> json_encode($bundle),
			'taxSystem'		=> 0
		], $this->getProp('PS_IS_TEST') === 'Y');

		if (!$result)
			throw new \Exception('empty response');

		if (isset($result['errorCode']))
			throw new \Exception($result['errorMessage'], $result['errorCode']);

		$result['request'] = [
			'payment' => $payment,
			'bundle' => $bundle
		];

		return $result;
	}

	public function checkOrder ($paymentId)
	{
		$payment = PaymentTable::getRow([
			'filter' => [
				'=ID' => (int) $paymentId,
				'!PS_INVOICE_ID' => false,
			]
		]);

		if (!$payment)
			throw new Exception('payment not found');

		return $this->checkOrderWithInvoice($payment['PS_INVOICE_ID'], $payment['PAY_SYSTEM_ID']);
	}

	public function checkOrderWithInvoice (string $invoiceId, int $paySystemId)
	{
		$this->setInternalId($paySystemId);

		return $this->sendRequest('getOrderStatus.do', [
			'userName' => $this->getProp('SBRF_LOGIN'),
			'password' => $this->getProp('SBRF_PASSWORD'),
			'orderId' => $invoiceId
		], $this->getProp('PS_IS_TEST') === 'Y');
	}

	public function setDocuments ($paymentId)
	{
		$payment = PaymentTable::getRow([
			'filter' => ['=ID' => (int) $paymentId]
		]);

		if (!$payment)
			throw new Exception('payment not found');

		$this->setInternalId($payment['PAY_SYSTEM_ID']);

		$result = $this->sendRequest('getReceiptStatus.do', [
			'userName' => $this->getProp('SBRF_LOGIN'),
			'password' => $this->getProp('SBRF_PASSWORD'),
			'orderId' => $payment['PS_INVOICE_ID']
		], $this->getProp('PS_IS_TEST') === 'Y');

		if ($result && (int) $result['errorCode'] == 0)
		{
			$cashbox = CashboxTable::getRow([
				'select' => ['ID'],
				'filter' => ['=HANDLER' => '\Olympia\Fssmo\Order\Cashbox\Fssmo']
			]);

			if ($cashbox)
			{
				foreach ($result['receipt'] as $doc)
				{
					if ((int) $doc['receipt_date_time'] <= 0)
						continue;

					$link = [
						Check::PARAM_REG_NUMBER_KKT => $doc['ecr_registration_number'],
						Check::PARAM_FISCAL_DOC_ATTR => $doc['fiscal_document_attribute'],
						Check::PARAM_FISCAL_DOC_NUMBER => $doc['fiscal_document_number'],
						Check::PARAM_FISCAL_RECEIPT_NUMBER => $doc['fiscal_receipt_number'],
						Check::PARAM_FN_NUMBER => $doc['fn_number'],
						Check::PARAM_SHIFT_NUMBER => $doc['shift_number'],
						Check::PARAM_DOC_SUM => $doc['amount_total'],
						Check::PARAM_DOC_TIME => floor($doc['receipt_date_time'] / 1000),
					];

					$date = DateTime::createFromTimestamp(floor($doc['receipt_date_time'] / 1000));

					CashboxCheckTable::add([
						'CASHBOX_ID' => $cashbox['ID'],
						'PAYMENT_ID' => $payment['ID'],
						'SHIPMENT_ID' => 0,
						'ORDER_ID' => $payment['ORDER_ID'],
						'SUM' => $doc['amount_total'] / 100,
						'CURRENCY' => $payment['CURRENCY'],
						'TYPE' => 'sell',
						'DATE_CREATE' => $date,
						'DATE_PRINT_START' => $date,
						'DATE_PRINT_END' => $date,
						'EXTERNAL_UUID' => $doc['uuid'],
						'STATUS' => 'Y',
						'LINK_PARAMS' => $link,
						'ENTITY_REGISTRY_TYPE' => 'ORDER'
					]);
				}
			}
		}
	}

	private function sendRequest ($method, $data, $testMode = false)
	{
		if ($testMode)
			$url = 'https://3dsec.sberbank.ru/payment/rest/';
		else
			$url = 'https://securepayments.sberbank.ru/payment/rest/';

		$httpClient = new HttpClient([
			'socketTimeout' => 45,
			'streamTimeout' => 45,
			'disableSslVerification' => true,
		]);

		$httpClient->setHeader('Content-type', 'application/x-www-form-urlencoded');
		$httpClient->setHeader('Cache-Control', 'no-cache');
		$httpClient->setHeader('Charset', 'utf-8');

		$response = $httpClient->post($url.$method, $data);

		if ($response === false)
			return false;

		return json_decode($response, true);
	}
}