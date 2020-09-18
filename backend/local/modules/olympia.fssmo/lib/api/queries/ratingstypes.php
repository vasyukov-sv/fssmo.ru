<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Rating;

class RatingsTypes
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$types = [
			1 => 'sporting',
			2 => 'sportingcompact',
			4 => 'sportingdoublets',
			9 => 'sportrap',
		];

		$result = [];

		$cache = Cache::createInstance();

		if ($cache->initCache(86400, 'RATING_TYPES', '/'))
			$result = $cache->getVars();
		else
		{
			$disciplines = External\DisciplinesTable::query()
				->setOrder(['id' => 'ASC'])
				->setSelect(['*'])
				->setFilter(['=id' => array_keys($types)])
				->exec();

			/** @var External\DisciplinesTable $discipline */
			foreach ($disciplines as $discipline)
			{
				$r = Rating::getByDisciplineId($discipline->id);
				$c = 0;

				foreach ($r as $i)
				{
					if ($i['rating'] > 0)
						$c++;
				}

				$result[] = [
					'id' => $discipline->id,
					'code' => $types[$discipline->id],
					'title' => $discipline->Discipline,
					'count' => $c,
				];
			}

			$count = 0;

			$connection = Application::getConnection(External\SuperFinalTable::getConnectionName());
			$resource = $connection->getResource();

			$stmt = sqlsrv_query($resource, "SELECT TOP 1 DataTableXML FROM SuperFinal ORDER BY ID DESC");

			if (sqlsrv_fetch($stmt) !== false)
			{
				$stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR));

				$st = '';

				while (!feof( $stream))
				    $st .= fread( $stream, 1000);

				$xml = simplexml_load_string($st);
				$count = count(json_decode(json_encode($xml), true)['Table1']);
			}

			$result[] = [
				'id' => -1,
				'code' => 'superfinal',
				'title' => 'Зачет суперфинала',
				'count' => $count,
			];

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}
}