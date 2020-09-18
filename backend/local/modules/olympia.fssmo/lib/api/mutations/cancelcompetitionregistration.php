<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\BasketTable;
use DateInterval;
use DateTime;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External\RegistredUsersTable;
use Olympia\Fssmo\Model\CompetitionsPriceTable;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo;

class CancelCompetitionRegistration
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		if ($context['user'] <= 0)
			throw new Exception('Необходимо войти в систему. Если у Вас нет аккаунта, зарегистрируйтесь');

		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['=ID' => $context['user']])
			->exec()->fetch();

		/** @var RegistredUsersTable $isRegistered */
		$isRegistered = RegistredUsersTable::query()
			->setSelect(['id', 'SiteCompId'])
			->setFilter([
				'=id' => (int) $args['id'],
				'=UserId' => $user['XML_ID'],
				'=Refused' => false,
				'=Banned' => false
			])
			->exec()->fetch();

		if (!$isRegistered)
			throw new Exception('Вы не зарегистрированы');

		$connection = Application::getConnection(RegistredUsersTable::getConnectionName());
		$connection->startTransaction();

		try
		{
			RegistredUsersTable::update($isRegistered['id'], [
				'Refused' => true
			]);

			$connection->commitTransaction();

			/** @var CompetitionsTable $competition */
			$competition = CompetitionsTable::query()
				->setSelect(['ID', 'PROPERTY.DATE_FROM'])
				->setFilter(['=ACTIVE' => 'Y', '=PROPERTY.EXTERNAL_ID' => $isRegistered->SiteCompId])
				->exec()->fetch();

			if ($competition)
			{
				$findInBasket = BasketTable::query()
					->setSelect(['ID', 'ORDER_ID'])
					->setFilter([
						'=PRODUCT.PROPERTY.CML2_LINK' => $competition->ID,
						'=ORDER.PAYED' => 'Y',
					])
					->registerRuntimeField((new Reference('PRODUCT', CompetitionsPriceTable::class, Join::on('this.PRODUCT_ID', 'ref.ID')))
						->configureJoinType('inner'))
					->exec()->fetch();

				if ($findInBasket)
				{
					$order = Fssmo\Sale\Order::load($findInBasket['ORDER_ID']);

					$date = new DateTime($competition->getProperty('DATE_FROM'));
					$date->sub(new DateInterval('P1D'));
					$date->setTime(16, 0, 0);

					if ($date->getTimestamp() > time())
						$order->refund($findInBasket['ID'], 100);
					else
						$order->cancel();
				}
			}
		}
		catch (\Exception $e)
		{
			$connection->rollbackTransaction();

			throw new Exception($e->getMessage());
		}

		return true;
	}
}