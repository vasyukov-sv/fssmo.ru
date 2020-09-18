<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\UserTable;
use Bitrix\Sale;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo;
use Olympia\Fssmo\Db\External\RegistredUsersTable;
use Olympia\Fssmo\Model\CompetitionsTable;

class CompetitionRegistration
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		if ($context['user'] <= 0)
			throw new Exception('Необходимо войти в систему. Если у Вас нет аккаунта, зарегистрируйтесь');

		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['=ID' => $context['user']])
			->exec()->fetch();

		/** @var CompetitionsTable $competition */
		$competition = CompetitionsTable::query()
			->setSelect(['ID', 'PROPERTY.EXTERNAL_ID'])
			->setFilter(['=ID' => (int) $args['competition']])
			->exec()->fetch();

		if (!$competition)
			throw new Exception('Соревнование не найдено');

		$isRegistered = RegistredUsersTable::query()
			->setSelect(['id'])
			->setFilter([
				'=SiteCompId' => $competition->getProperty('EXTERNAL_ID'),
				'=UserId' => $user['XML_ID'],
				'=Refused' => false,
				'=Banned' => false
			])
			->exec()->fetch();

		if ($isRegistered)
			throw new Exception('Вы уже зарегистрированы');

		$paymentType = $args['payment'] ?? 'offline';

		if ($paymentType == 'online' || $paymentType == 'balance')
		{
			$orderId = Fssmo\Competition\Order::createOrder(
				$competition['ID'],
				$context['user'],
				$paymentType == 'balance'
			);

			$basketItem = Sale\Internals\BasketTable::query()
				->setSelect(['ID'])
				->setFilter(['=ORDER_ID' => $orderId])
				->exec()->fetch();

			if ($basketItem)
			{
				$order = Fssmo\Sale\Order::load($orderId);

				if ($order->isPaid())
				{
					Fssmo\Competition::registration(
						$competition->ID,
						$context['user'],
						$args['data']
					);
				}
				else
				{
					$profileEntity = _hl(4);
					$profileEntity::add([
						'UF_BASKET_ID' => $basketItem['ID'],
						'UF_DATA' => serialize($args['data']),
					]);
				}
			}

			$payment = Fssmo\Competition\Order::getPaymentData($orderId);

			if (!count($payment))
			{
				return [
					'success' => true,
				];
			}

			return [
				'success' => true,
				'order' => [
					'id' => $orderId,
					'payment' => $payment,
				],
			];
		}
		else
		{
			Fssmo\Competition::registration(
				$competition->ID,
				$context['user'],
				$args['data']
			);
		}

		return [
			'success' => true,
		];
	}
}