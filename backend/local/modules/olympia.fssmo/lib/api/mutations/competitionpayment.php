<?php

namespace Olympia\Fssmo\Api\Mutations;

use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo;

class CompetitionPayment
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		if ($context['user'] <= 0)
			throw new Exception('Необходимо войти в систему. Если у Вас нет аккаунта, зарегистрируйтесь');

		/** @var CompetitionsTable $competition */
		$competition = CompetitionsTable::query()
			->setSelect(['ID'])
			->setFilter(['=ID' => (int) $args['competition']])
			->exec()->fetch();

		if (!$competition)
			throw new Exception('Соревнование не найдено');

		$orderId = Fssmo\Competition\Order::createOrder(
			$competition['ID'],
			$context['user']
		);

		return [
			'id' => $orderId,
			'payment' => Fssmo\Competition\Order::getPaymentData($orderId),
		];
	}
}