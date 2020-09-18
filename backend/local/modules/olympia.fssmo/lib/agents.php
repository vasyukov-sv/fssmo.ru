<?php

namespace Olympia\Fssmo;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Location\LocationTable;
use CUser;
use Olympia\Fssmo\Model\CalendarTable;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo\Db\External;
use Bitrix\Sale;
use Olympia\Fssmo;

class Agents
{
	public static function clearUnpayedOrders ()
	{
		global $USER;

		$USER = new CUser();

		Loader::includeModule('sale');

		$orders = Sale\Internals\OrderTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=PAYED' => 'N',
				'=STATUS_ID' => 'N',
				'=CANCELED' => 'N',
				'<DATE_INSERT' => DateTime::createFromTimestamp(time() - 3 * 86400)
			],
			'limit' => 5
		]);

		while ($order = $orders->fetch())
		{
			Fssmo\Sale\Order::deleteNoDemand($order['ID']);
		}

		unset($USER);

		return '\Olympia\Fssmo\Agents::clearUnpayedOrders();';
	}

	public static function syncCalendar ()
	{
		Loader::includeModule('sale');

		$targets = [
			50 => 592,
			100 => 593,
			150 => 594,
			200 => 595,
		];

		$countryRussia = LocationTable::query()
			->setSelect(['ID'])
			->setFilter(['=TYPE.CODE' => 'COUNTRY', '=NAME.LANGUAGE_ID' => LANGUAGE_ID, '=NAME.NAME' => 'Россия'])
			->exec()->fetch();

		$items = CompetitionsTable::query()
			->setSelect(['ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY.EXTERNAL_ID', 'PROPERTY.DATE_FROM', 'PROPERTY.DATE_TO', 'PROPERTY.DISCIPLINE', 'PROPERTY.LOCATION'])
			->setFilter(['=ACTIVE' => 'Y'])
			->setOffset(10)
			->exec();

		/** @var CompetitionsTable $item */
		foreach ($items as $item)
		{
			$finder = CalendarTable::query()
				->setSelect(['ID'])
				->setFilter(['=XML_ID' => 'EVENT_'.$item['ID']])
				->exec()->fetch();

			$duration = (strtotime($item['PROPERTY_DATE_TO']) - strtotime($item['PROPERTY_DATE_FROM'])) / 86400;

			/** @var External\CompetitionsTable $comp */
			$comp = External\CompetitionsTable::query()
				->setSelect(['id', 'ClubId', 'TargetsCount'])
				->setFilter(['=SiteId' => $item->getProperty('EXTERNAL_ID')])
				->exec()->fetch();

			$country = $countryRussia['ID'] ?? '';
			$district = '';
			$city = trim($item['PROPERTY_LOCATION']);
			$cityId = 0;

			$findCity =  LocationTable::query()
				->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
				->setFilter(['=TYPE.CODE' => 'CITY', '=NAME.LANGUAGE_ID' => LANGUAGE_ID, '=NAME.NAME' => $city])
				->exec()->fetch();

			if ($findCity)
			{
				$city = $findCity['LOCATION_NAME'];
				$cityId = $findCity['ID'];
			}

			if (!$cityId)
			{
				/** @var External\ClubsTable $location */
				$location = External\ClubsTable::query()
					->setSelect(['id', 'Address', 'Country.Country', 'Town.Town'])
					->where('ClubName', $city)
					->exec()->fetch();

				if (!$location && $comp->ClubId > 1)
				{
					/** @var External\ClubsTable $location */
					$location = External\ClubsTable::query()
						->setSelect(['id', 'Address', 'Country.Country', 'Town.Town'])
						->setFilter(['=id' => $comp->ClubId])
						->exec()->fetch();
				}

				if ($location)
				{
					$findCity = LocationTable::query()
						->setSelect(['ID', 'LOCATION_NAME' => 'NAME.NAME'])
						->setFilter(['=TYPE.CODE' => 'CITY', '=NAME.LANGUAGE_ID' => LANGUAGE_ID, '=NAME.NAME' => $location->Town->Town ? $location->Town->Town : $location->Address])
						->exec()->fetch();

					if ($findCity)
					{
						$city = $findCity['LOCATION_NAME'];
						$cityId = $findCity['ID'];
					}
				}
			}

			if ($cityId > 0)
			{
				$cityTmp = LocationTable::query()
					->setSelect(['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'COUNTRY_ID'])
					->setFilter(['=ID' => $cityId])
					->exec()->fetch();

				$findDistrict = LocationTable::query()
					->setSelect(['ID'])
					->setFilter(['=TYPE.CODE' => 'COUNTRY_DISTRICT', '<LEFT_MARGIN' => $cityTmp['LEFT_MARGIN'], '>RIGHT_MARGIN' => $cityTmp['RIGHT_MARGIN']])
					->exec()->fetch();

				if ($findDistrict)
					$district = $findDistrict['ID'];

				$country = $cityTmp['COUNTRY_ID'];
			}

			$props = [
				'ACTIVE_FROM' => date("d.m.Y H:i:s", strtotime($item['PROPERTY_DATE_FROM'])),
				'ACTIVE_TO' => date("d.m.Y H:i:s", strtotime($item['PROPERTY_DATE_TO'])),
				'DISCIPLINE' => (int) $item['PROPERTY_DISCIPLINE'],
				'STATUS' => '',
				'TARGETS' => $comp && $comp->TargetsCount > 0 && isset($targets[$comp->TargetsCount]) ? $targets[$comp->TargetsCount] : false,
				'CLUB' => trim($item['PROPERTY_LOCATION']),

				'COUNTRY' => $country,
				'DISTRICT' => $district,
				'CITY' => $city,

				'RULES' => 'https://fssmo.ru'.$item['DETAIL_PAGE_URL'],
				'EMAIL' => 'fssmo@mail.ru',
				'PHONE' => '8 (916) 339-63-66',
				'SITE' => 'https://fssmo.ru',
				'DURATION' => $duration,
			];

			if (!$finder)
			{
				CalendarTable::add([
					'NAME' => $item['NAME'],
					'ACTIVE' => 'Y',
					'XML_ID' => 'EVENT_'.$item['ID'],
					'PROPERTY' => $props,
				]);
			}
			else
			{
				CalendarTable::update($finder['ID'], [
					'NAME' => $item['NAME'],
					'PROPERTY' => $props,
				]);
			}
		}

		return '\Olympia\Fssmo\Agents::syncCalendar();';
	}

	public static function checkPayments ($userId = false)
	{
		$filter = [
			'=PAY_SYSTEM.ACTIVE' => 'Y',
			'=PAY_SYSTEM.ACTION_FILE' => 'sbrf',
			'=PAID' => 'N',
			'=ORDER.CANCELED' => 'N',
			'=DATE_PAID' => false,
			'>=DATE_BILL' => DateTime::createFromTimestamp(time() - 1800)
		];

		if ($userId && $userId > 0)
		{
			$filter['=ORDER.USER_ID'] = (int) $userId;
			unset($filter['>=DATE_BILL']);
		}

		$payments = Sale\Internals\PaymentTable::getList([
			'select' => ['ID', 'ORDER_ID', 'CURRENCY', 'PAY_SYSTEM_ID', 'CODE' => 'PAY_SYSTEM.ACTION_FILE'],
			'filter' => $filter,
		]);

		foreach ($payments as $pay)
		{
			if ($pay['CODE'] == 'sbrf')
			{
				$api = new Fssmo\Sale\Payments\Sberbank();

				try
				{
					$response = $api->checkOrder($pay['ID']);

					if ($response && (int) $response['ErrorCode'] == 0 && (int) $response['OrderStatus'] == 2)
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

						$order = Fssmo\Sale\Order::load($pay['ORDER_ID']);

						/** @var Sale\PaymentCollection $collection */
						$collection = $order->getPaymentCollection();
						/** @var Sale\Payment $payment */
						$payment = $collection->getItemById($pay['ID']);

						$payment->setPaid('Y');
						$payment->setFields($fields);
						$order->save();
					}
				}
				catch (\Exception $e) {}
			}
		}

		return '\Olympia\Fssmo\Agents::checkPayments();';
	}
}