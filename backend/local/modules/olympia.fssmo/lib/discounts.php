<?php

namespace Olympia\Fssmo;

use Bitrix\Catalog\PriceTable;
use Bitrix\Sale;
use CCurrencyLang;
use CIBlockPriceTools;
use Olympia\Fssmo;

class Discounts
{
	public static function getPrice ($productId)
	{
		if (!is_array($productId))
			$productId = [$productId];

		$basePrice = CIBlockPriceTools::GetCatalogPrices(false, ['BASE']);

		$rows = PriceTable::query()
			->setSelect(['ID', 'PRICE', 'CURRENCY', 'PRODUCT_ID'])
			->setFilter(['=PRODUCT_ID' => $productId, '@CATALOG_GROUP_ID' => $basePrice['BASE']])
			->exec();

		$prices = [];

		foreach ($rows as $row)
			$prices[$row['PRODUCT_ID']] = $row;

		if (!count($prices))
			return false;

		global $USER;

		$order = Fssmo\Sale\Order::create(SITE_ID, $USER->GetID());
		$basket = Sale\Basket::create($order->getSiteId());

		foreach ($prices as $price)
		{
			$basketItem = $basket->createItem('catalog', $price['PRODUCT_ID']);
			$basketItem->setFields([
				'QUANTITY' => 1,
				'LID' => SITE_ID,
				'PRODUCT_PRICE_ID' => $price['ID'],
				'BASE_PRICE' => $price['PRICE'],
				'PRICE' => $price['PRICE'],
				'CURRENCY' => $price['CURRENCY'],
				'CAN_BUY' => 'Y'
			]);
		}

		$order->setBasket($basket);
		$order->refreshData();

		$result = [];

		/** @var Sale\BasketItem $basketItem */
		foreach ($basket->getBasketItems() as $basketItem)
		{
			$row = [
				'ID' => (int) $basketItem->getField('PRODUCT_ID'),
				'CURRENCY' => $basketItem->getField('CURRENCY'),
				'ORIGINAL_VALUE' => (float) $basketItem->getField('PRICE') + (float) $basketItem->getField('DISCOUNT_PRICE'),
				'VALUE' => (float) $basketItem->getField('PRICE'),
				'DISCOUNT' => (float) $basketItem->getField('DISCOUNT_PRICE'),
			];

			$row['ORIGINAL_VALUE_FORMATTED'] = CCurrencyLang::CurrencyFormat($row['ORIGINAL_VALUE'], $row['CURRENCY']);
			$row['VALUE_FORMATTED'] = CCurrencyLang::CurrencyFormat($row['VALUE'], $row['CURRENCY']);
			$row['DISCOUNT_FORMATTED'] = CCurrencyLang::CurrencyFormat($row['DISCOUNT'], $row['CURRENCY']);

			$result[(int) $basketItem->getField('PRODUCT_ID')] = $row;
		}

		return $result;
	}
}