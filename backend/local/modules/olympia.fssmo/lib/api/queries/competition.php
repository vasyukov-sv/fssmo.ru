<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\UserTable;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Db\External\RegistredUsersTable;
use Olympia\Fssmo\Model;
use Olympia\Fssmo;

class Competition
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$competition = Model\CompetitionsTable::query()
			->setSelect([
				'ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL',
				'PREVIEW_PICTURE',
				'DETAIL_TEXT',
				'PROPERTY.LOCATION',
				'PROPERTY.DATE_FROM',
				'PROPERTY.DATE_TO',
				'PROPERTY.URL',
				'DISCIPLINE.NAME',
				'PROPERTY.EXTERNAL_ID',
				'PROPERTY.PHOTO',
				'PROPERTY.MAX_SHOOTERS',
				'PROPERTY.REGISTRATION',
				'PROPERTY.PROTOCOLS',
			])
			->setFilter(['=ACTIVE' => 'Y']);

		if (is_numeric($args['id']))
			$competition->addFilter('=ID', (int) $args['id']);
		else
			$competition->addFilter('=CODE', trim($args['id']));

		$competition = $competition->exec()->fetch();
		/** @var Model\CompetitionsTable $competition */

		if (!$competition)
			throw new Exception('Соревнование не найдено');

		/** @var External\CompetitionsTable $comp */
		$comp = External\CompetitionsTable::query()
			->setSelect(['id', 'TargetsCount', 'StandsCount'])
			->setFilter(['=SiteId' => $competition->getProperty('EXTERNAL_ID')])
			->exec()->fetch();

		$location = trim((string) $competition->getProperty('LOCATION'));

		if ($location == '-')
			$location = '';

		$dateFrom = null;
		$dateTo = null;

		if ($competition->getProperty('DATE_FROM'))
			$dateFrom = date('Y-m-d\TH:i:s', strtotime($competition->getProperty('DATE_FROM')));

		if ($competition->getProperty('DATE_TO'))
			$dateTo = date('Y-m-d\TH:i:s', strtotime($competition->getProperty('DATE_TO')));

		$tabs = [
			'schedule' => false,
			'groups' => false,
			'participants' => false,
			'results' => false,
			'winners' => false,
			'photo' => false,
			'about' => false,
		];

		if ($competition->DETAIL_TEXT != '')
			$tabs['about'] = true;

		if (is_array($competition->getProperty('PHOTO')) && count($competition->getProperty('PHOTO')))
			$tabs['photo'] = true;

		$shooters = External\RegistredUsersTable::getCount([
			'=SiteCompId' => $competition->getProperty('EXTERNAL_ID'),
			'=Refused' => false,
			'=Banned' => false,
		]);

		if ($shooters > 0)
			$tabs['participants'] = true;

		if ($comp)
		{
			$tabs['results'] = External\ResultsTable::getCount([
				'=CompId' => $comp->id
			], ['ttl' => 3600]) > 0;

			/** @var External\ShedulesTable $shedule */
			$shedule = External\ShedulesTable::query()
				->setSelect(['id', 'SheduleJSON'])
				->setFilter(['=CompId' => $comp->id])
				->setCacheTtl(3600)
				->exec()->fetch();

			if (is_array($shedule->SheduleJSON))
				$tabs['schedule'] = true;

			$tabs['groups'] = External\CompShootersTable::getCount([
				'=CompId' => $comp->id,
				'>GroupNumber' => 0
			], ['ttl' => 3600]) > 0;

			if (!$comp->StandsCount)
				$comp->StandsCount = 8;

			if (strtotime($dateTo) < time())
				$tabs['winners'] = true;
		}

		$registration = $competition->getProperty('REGISTRATION') > 0;

		if ($dateFrom && strtotime($competition->getProperty('DATE_FROM')) < time())
			$registration = false;

		if ((int) $shooters >= ((int) $competition->getProperty('MAX_SHOOTERS') ?? 100))
			$registration = false;

		if ($registration && $context['user'] > 0)
		{
			$user = UserTable::query()
				->setSelect(['ID', 'XML_ID'])
				->setFilter(['=ID' => $context['user']])
				->exec()->fetch();

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
				$registration = false;
		}

		$protocols = [];

		if (is_array($competition->getProperty('PROTOCOLS')))
		{
			foreach ($competition->getProperty('PROTOCOLS') as $file)
				$protocols[] = \CFile::GetFileArray($file)['SRC'];
		}

		$offer = Fssmo\Competition\Order::getOfferByRestrictions($competition->ID, $context['user']);

		return [
			'id' => $competition->ID,
			'title' => $competition->NAME,
			'url' => $competition->DETAIL_PAGE_URL,
			'location' => $location,
			'date_from' => $dateFrom,
			'date_to' => $dateTo,
			'discipline' => $competition->DISCIPLINE ? (string) $competition->DISCIPLINE->NAME : '',
			'targets' => $comp->TargetsCount,
			'stands' => $comp->StandsCount,
			'shooters' => $shooters,
			'max_shooters' => (int) $competition->getProperty('MAX_SHOOTERS') ?? 100,
			'detail_text' => $competition->DETAIL_TEXT,
			'registration' => $registration,
			'tabs' => $tabs,
			'protocols' => $protocols,
			'price' => $offer ? [
				'currency' => $offer['PRICE_CURRENCY'],
				'value' => (float) $offer['PRICE_PRICE'],
			] : null
		];
	}
}