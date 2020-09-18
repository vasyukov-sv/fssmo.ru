<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Currency\CurrencyManager;
use Olympia\Fssmo\Model\ServicesTable;

class EnterInClub
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$result = [
			'exist' => false,
			'price' => [
				'currency' => CurrencyManager::getBaseCurrency(),
				'value' => 0,
			],
			'items' => [],
		];

		$services = [];

		$items = ServicesTable::query()
			->setSelect(['ID', 'CODE', 'PRICE_PRICE' => 'PRICE.PRICE', 'PRICE_CURRENCY' => 'PRICE.CURRENCY'])
			->setFilter(['=CODE' => ['FSSMO_ENTRANCE_FEE', 'FSSMO_MEMBERSHIP_FEE']])
			->exec();

		foreach ($items as $item)
			$services[$item['CODE']] = $item;

		foreach ($services as $service)
		{
			$result['price']['value'] += (float) $service['PRICE_PRICE'];

			$result['items'][$service['CODE']] = [
				'currency' => $service['PRICE_CURRENCY'],
				'value' => (float) $service['PRICE_PRICE'],
			];
		}

		return $result;
	}
}