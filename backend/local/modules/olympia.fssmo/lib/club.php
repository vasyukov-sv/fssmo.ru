<?php

namespace Olympia\Fssmo;

use Bitrix\Main\Application;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Model\ServicesTable;
use Bitrix\Sale;
use Bitrix\Catalog;
use Olympia\Fssmo;

class Club
{
	public static function createOrder (int $userId, bool $renewal = false, bool $payFromBalance = false): int
	{
		$context = Application::getInstance()->getContext();

		$basket = Sale\Basket::create($context->getSite());
		$basket->setFUserId(Sale\Fuser::getIdByUserId($userId));

		$codes = ['FSSMO_MEMBERSHIP_FEE'];

		if (!$renewal)
			$codes[] = 'FSSMO_ENTRANCE_FEE';

		$items = ServicesTable::query()
			->setSelect([
				'ID',
				'CODE',
				'NAME',
				'PRICE_PRICE' => 'PRICE.PRICE',
				'PRICE_CURRENCY' => 'PRICE.CURRENCY'
			])
			->setFilter(['=CODE' => $codes])
			->exec();

		/** @var ServicesTable $item */
		foreach ($items as $item)
		{
			$res = Catalog\Product\Basket::addProductToBasket($basket, [
				'PRODUCT_ID' => $item->ID,
				'QUANTITY' => 1,
				'PRICE' => $item['PRICE_PRICE'],
				'BASE_PRICE' => $item['PRICE_PRICE'],
				'CURRENCY' => $item['PRICE_CURRENCY'],
			], [
				'SITE_ID' => $context->getSite(),
				'USER_ID' => $userId,
			]);

			if (!$res->isSuccess())
				throw new Exception('basket item create error: '.implode(', ', $res->getErrorMessages()));
		}

		$order = Fssmo\Sale\Order::createFromBasket($userId, $basket);
		$order->addPaymentById(Fssmo\Competition\Order::DEFAULT_PAYMENT_ID, $payFromBalance);

		$res = $order->save();

		if (!$res->isSuccess())
			throw new Exception('order create error: '.implode(', ', $res->getErrorMessages()));

		return (int) $order->getId();
	}
}