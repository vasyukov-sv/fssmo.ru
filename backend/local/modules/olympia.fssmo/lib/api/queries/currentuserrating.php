<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model\DisciplinesTable;
use Olympia\Fssmo\Rating;

class CurrentUserRating
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		if (!is_integer($context['user']))
			throw new Exception('not login');

		$user = UserTable::query()
			->setSelect(['ID', 'XML_ID'])
			->setFilter(['=ID' => $context['user']])
			->exec()->fetch();

		/** @var External\ShootersTable $shooter */
		$shooter = External\ShootersTable::query()
			->setSelect(['id'])
			->setFilter(['=UserId' => $user['XML_ID']])
			->exec()->fetch();

		if (!$shooter)
			return [];

		$cache = Cache::createInstance();

		if ($cache->initCache(3600, 'RATING_ACTUAL_SHOOTER|'.$shooter->id.'|', '/'))
			$result = $cache->getVars();
		else
		{
			$items = DisciplinesTable::query()
				->setOrder(['SORT' => 'ASC'])
				->setSelect(['ID', 'NAME', 'XML_ID'])
				->setFilter(['=ACTIVE' => 'Y'])
				->exec();

			$disciplines = [];

			/** @var DisciplinesTable $item */
			foreach ($items as $item)
			{
				$disciplines[] = [
					'id' => (int) $item->XML_ID,
					'title' => (string) $item->NAME,
				];
			}

			$endDate = DateTime::createFromTimestamp(time());
			$startDate = clone $endDate;

			if ((int) $endDate->format('w') === 0)
				$startDate->add('-1 year -2 days');
			elseif ((int) $endDate->format('w') === 6)
				$startDate->add('-1 year -1 days');
			else
				$startDate->add('-1 year');

			$result = [];

			foreach ($disciplines as $discipline)
			{
				$competitions = [];

				$items = External\CompetitionsTable::query()
					->setSelect(['id'])
					->setFilter([
						'=IsRating' => true,
						'=DisciplineId' => $discipline['id'],
						'>BeginDate' => $startDate,
						'<=BeginDate' => $endDate,
					])
					->exec();

				/** @var External\CompetitionsTable $item */
				foreach ($items as $item)
				{
					$competitions[] = $item->id;
				}

				$rating = Rating::calculateByCompetitions($competitions);

				foreach ($rating as $n => $r)
				{
					if ($r['shooter'] == $shooter->id)
					{
						$result[] = [
							'discipline' => $discipline['title'],
							'rating' => $r['rating'],
							'place' => $n,
							'group' => $r['group'],
						];
					}
				}
			}

			$connection = Application::getConnection(External\SuperFinalTable::getConnectionName());
			$resource = $connection->getResource();

			$stmt = sqlsrv_query($resource, "SELECT TOP 1 DataTableXML FROM SuperFinal ORDER BY ID DESC");

			if (sqlsrv_fetch($stmt) === false)
				throw new Exception('error parsing data');

			$stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR));

			$st = '';

			while (!feof( $stream))
				$st .= fread( $stream, 1000);

			$xml = simplexml_load_string($st);
			$items = json_decode(json_encode($xml), true)['Table1'];

			foreach ($items as $item)
			{
				if ($item['sid'] == $shooter->id)
				{
					$result[] = [
						'discipline' => 'Суперфинал',
						'rating' => (float) $item['r'],
						'place' => (int) $item['num'],
						'group' => $item['g'],
					];
				}
			}

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}