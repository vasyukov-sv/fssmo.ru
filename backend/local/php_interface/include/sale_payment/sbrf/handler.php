<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\PaymentTable;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Main\Request;
use Bitrix\Main\Error;
use Bitrix\Sale\PaySystem\Logger;
use Olympia\Fssmo\Exception;
use Olympia\Fssmo\Helpers;
use Olympia\Fssmo\Sale\Payments\Sberbank;

Loc::loadMessages(__FILE__);

class SbrfHandler extends PaySystem\ServiceHandler
{
	/**
	 * @return array
	 */
	static public function getIndicativeFields()
	{
		return array('orderId');
	}

	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 * @throws \Exception
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		if (!$payment->getField('PS_INVOICE_ID'))
		{
			$paymentData = [];

			foreach ($this->getBusinessCodes() as $code)
				$paymentData[$code] = $this->getBusinessValue($payment, $code);

			$api = new Sberbank();

			try {
				$response = $api->createOrder($payment->getId(), $paymentData);

				if (!isset($response['orderId']) || !Helpers::isValidUuid($response['orderId']))
					throw new \Exception('invalid orderId: '.$response['orderId']);
			}
			catch (\Exception $e)
			{
				Logger::addError('[SbrfHandler\initiatePay] '.$e->getCode().':'.$e->getMessage());

				throw new Exception('[sbrf payment handler] '.$e->getMessage());
			}

			$payment->setField('PS_INVOICE_ID', $response['orderId']);
			$payment->save();

			$this->setExtraParams([
				'URL' => $response['formUrl']
			]);
		}
		else
		{
			if ($this->isTestMode())
				$url = 'https://3dsec.sberbank.ru/payment/';
			else
				$url = 'https://securepayments.sberbank.ru/payment/';

			$url .= 'merchants/'.$this->getBusinessValue($payment, 'SBRF_NAME').'/payment_'.mb_strtolower(LANGUAGE_ID).'.html?mdOrder='.$payment->getField('PS_INVOICE_ID');

			$this->setExtraParams([
				'URL' => $url
			]);
		}

		return $this->showTemplate($payment, 'template');
	}

	/**
	 * @param Payment $payment
	 * @return bool
	 */
	protected function isTestMode(Payment $payment = null)
	{
		return ($this->getBusinessValue($payment, 'PS_IS_TEST') == 'Y');
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws \Exception
	 */
	public function processRequest(Payment $payment, Request $request)
	{
		/** @var PaySystem\ServiceResult $result */
		$result = new PaySystem\ServiceResult();

		$pay = PaymentTable::getRow([
			'select' => ['ID', 'CURRENCY', 'ORDER_ID'],
			'filter' => ['=PS_INVOICE_ID' => $request->get('orderId')]
		]);

		$api = new Sberbank();

		if ($pay)
		{
			try {
				$response = $api->checkOrder($pay['ID']);
			}
			catch (Exception $e) {
				$response = false;
			}

			if ($response)
			{
				if ((int) $response['ErrorCode'] == 0 && (int) $response['OrderStatus'] == 2)
				{
					$fields = [
						"PS_STATUS" => "Y",
						"PS_STATUS_CODE" => $response['OrderStatus'],
						"PS_STATUS_DESCRIPTION" => $response['Pan'].';'.$response['Ip'].';'.$response['cardholderName'],
						"PS_STATUS_MESSAGE" => $response['ErrorMessage'],
						"PS_SUM" => $response['Amount'] / 100,
						"PS_CURRENCY" => $pay['CURRENCY'],
						"PS_RESPONSE_DATE" => new DateTime()
					];

					$result->setPsData($fields);
					$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
				}
				else
				{
					$fields = [
						"PS_STATUS" => "N",
						"PS_STATUS_CODE" => $response['OrderStatus'],
						"PS_STATUS_DESCRIPTION" => $response['Pan'].';'.$response['Ip'].';'.$response['cardholderName'],
						"PS_STATUS_MESSAGE" => $response['ErrorMessage'],
					];

					$result->setPsData($fields);
					$result->addError(new Error($response['ErrorMessage']));
				}
			}
			else
				$result->addError(new Error('Incorrect request result'));
		}
		else
			$result->addError(new Error('Incorrect order'));

		if (!$result->isSuccess())
			PaySystem\Logger::addError('Sberbank: processRequest: '.join('\n', $result->getErrorMessages()));

		$redirectUrl = $api->getProp('SBRF_SUCCESS_URL');

		if ($result->getOperationType() !== PaySystem\ServiceResult::MONEY_COMING)
			$redirectUrl = $api->getProp('SBRF_FAIL_URL');

		if (!$result->isSuccess())
			$redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&').'error='.$result->getErrorMessages()[0] ?? 'unknown error';

		$result->setData([
			'ORDER_ID' => $pay['ORDER_ID'],
			'REDIRECT_URL' => $redirectUrl,
		]);

		return $result;
	}

	/**
	 * @param Request $request
	 * @return string
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
		$payment = PaymentTable::getRow([
			'select' => ['ID'],
			'filter' => ['=PS_INVOICE_ID' => $request->get('orderId')]
		]);

		return $payment ? $payment['ID'] : false;
	}

	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return array('RUB');
	}

	/**
	 * @param PaySystem\ServiceResult $result
	 * @param Request $request
	 * @return mixed|void
	 */
	public function sendResponse(PaySystem\ServiceResult $result, Request $request)
	{
		$data = $result->getData();

		if (isset($data['ORDER_ID']) && (int) $data['ORDER_ID'] > 0 && $result->isSuccess() && $result->getOperationType() == PaySystem\ServiceResult::MONEY_COMING)
		{
			$event = new Event('sale', 'OnSalePsServiceProcessRequestAfterPaid',
				array(
					'order_id' => (int) $data['ORDER_ID']
				)
			);

			$event->send();
		}

		$redirectUrl = $data['REDIRECT_URL'];
		$redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&').'order_id='.intval($data['ORDER_ID']);

		LocalRedirect($redirectUrl, true);
	}
}