<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\BasketTable;
use CFile;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model\CompetitionsPriceTable;
use Olympia\Fssmo\Model\CompetitionsTable;
use Olympia\Fssmo;

class CurrentUserCompetitions
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		if (!is_integer($context['user']))
			throw new Exception('not login');

		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['=ID' => $context['user']])
			->exec()->fetch();

		$items = External\RegistredUsersTable::query()
			->setOrder(['RegistrationDate' => 'DESC'])
			->setSelect([
				'id', 'RegistrationDate', 'Competition.id'
			])
			->setFilter([
				'=UserId' => $user['XML_ID'],
				'=Refused' => false,
				'=Banned' => false,
				'>=Competition.BeginDate' => Date::createFromTimestamp(time())
			])
			->registerRuntimeField((new Reference('Competition',
				External\CompetitionsCalendarTable::class,
					Join::on('this.SiteCompId', 'ref.id')
				))->configureJoinType('inner')
			)
			->setLimit(10)
			->exec();

		$compId = [];
		$tmps = [];

		/** @var External\RegistredUsersTable $item */
		foreach ($items as $item)
		{
			$compId[] = $item['Competition']->id;
			$tmps[] = $item;
		}

		$items = CompetitionsTable::query()
			->setSelect([
				'ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL',
				'PREVIEW_PICTURE',
				'PROPERTY.LOCATION',
				'PROPERTY.DATE_FROM',
				'PROPERTY.DATE_TO',
				'PROPERTY.URL',
				'PROPERTY.EXTERNAL_ID',
				'DISCIPLINE.NAME',
			])
			->setFilter(['=ACTIVE' => 'Y', '=PROPERTY.EXTERNAL_ID' => array_unique($compId)])
			->exec();

		$competitions = [];
		$compId = [];

		/** @var CompetitionsTable $item */
		foreach ($items as $item)
		{
			$competitions[$item->getProperty('EXTERNAL_ID')] = $item;
			$compId[] =  (int) $item->ID;
		}

		$offersId = [];

		$items = CompetitionsPriceTable::query()
			->setSelect(['ID', 'PROPERTY.CML2_LINK'])
			->setFilter(['=PROPERTY.CML2_LINK' => $compId])
			->exec();

		/** @var CompetitionsPriceTable $item */
		foreach ($items as $item)
			$offersId[(int) $item->ID] = (int) $item->getProperty('CML2_LINK');

		$orders = [];

		$items = BasketTable::query()
			->setSelect(['ID', 'ORDER_ID', 'PRICE', 'CURRENCY', 'PRODUCT_ID'])
			->setFilter(['=PRODUCT_ID' => array_keys($offersId), '=ORDER.PAYED' => 'Y', '=ORDER.USER_ID' => $context['user']])
			->exec();

		foreach ($items as $item)
		{
			$orders[$item['PRODUCT_ID']] = $item;
		}

		$result = [];

		foreach ($tmps as $item)
		{
			$row = [
				'id' => $item->id,
				'date' => $item->RegistrationDate ? $item->RegistrationDate->format('c') : null,
				'competition' => null,
				'order' => null,
				'price' => null,
			];

			if (isset($competitions[$item['Competition']->id]))
			{
				$c = $competitions[$item['Competition']->id];

				$dateFrom = null;
				$dateTo = null;

				if ($c->getProperty('DATE_FROM'))
					$dateFrom = date('Y-m-d\TH:i:s', strtotime($c->getProperty('DATE_FROM')));

				if ($c->getProperty('DATE_TO'))
					$dateTo = date('Y-m-d\TH:i:s', strtotime($c->getProperty('DATE_TO')));

				$url = $c->DETAIL_PAGE_URL;

				if ($c->getProperty('URL') != '')
					$url = $c->getProperty('URL');

				$location = (string) $c->getProperty('LOCATION');

				if ($location == '-')
					$location = '';

				$image = null;

				if ($c->PREVIEW_PICTURE > 0)
					$image = CFile::ResizeImageGet($c->PREVIEW_PICTURE, ['width' => 450, 'height' => 310], BX_RESIZE_IMAGE_EXACT)['src'];

				$row['competition'] = [
					'id' => $c->ID,
					'code' => $c->CODE,
					'title' => $c->NAME,
					'url' => $url,
					'discipline' => $c->DISCIPLINE ? (string) $c->DISCIPLINE->NAME : '',
					'location' => $location,
					'date_from' => $dateFrom,
					'date_to' => $dateTo,
					'image' => $image,
				];

				foreach ($offersId as $offerId => $compId)
				{
					if ($compId != $c->ID)
						continue;

					if (isset($orders[$offerId]))
					{
						$row['order'] = [
							'id' => (int) $orders[$offerId]['ORDER_ID'],
							'price' => [
								'currency' => $orders[$offerId]['CURRENCY'],
								'value' => (float) $orders[$offerId]['PRICE'],
							]
						];
					}
				}

				if (!$row['order'])
				{
					$offer = Fssmo\Competition\Order::getOfferByRestrictions($c->ID, $context['user']);

					if ($offer)
					{
						$row['price'] = [
							'currency' => $offer['PRICE_CURRENCY'],
							'value' => (float) $offer['PRICE_PRICE'],
						];
					}
				}
			}

			$result[] = $row;
		}

		return $result;
	}
}