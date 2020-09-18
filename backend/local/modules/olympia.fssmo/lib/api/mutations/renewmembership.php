<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;
use Olympia\Bitrix\Helpers;
use Olympia\Fssmo;
use Olympia\Fssmo\Model\EnterTable;
use Olympia\Fssmo\Db\External\ClubsTable;

class RenewMembership
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		Loader::includeModule('iblock');

		$paymentType = $args['payment'] ?? 'online';

		$orderId = Fssmo\Club::createOrder(
			$context['user'],
			true,
			$paymentType == 'balance'
		);

		return [
			'success' => true,
			'order' => [
				'id' => $orderId,
				'payment' => Fssmo\Competition\Order::getPaymentData($orderId),
			],
		];
	}
}