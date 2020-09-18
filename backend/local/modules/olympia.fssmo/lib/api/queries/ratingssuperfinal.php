<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Application;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;

class RatingsSuperfinal
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$page = $args['page'] ?? 1;
		$page = max(1, min(99, $page));

		$limit = $args['limit'] ?? 10;
		$limit = max(1, min(100, $limit));

		$result = [
			'items' => [],
			'pagination' => [
				'total' => 0,
				'limit' => $limit,
				'page' => $page,
			]
		];

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

		$result['pagination']['total'] = count($items);

		$rating = array_slice($items, ($page - 1) * $limit, $limit, true);

		$compId = [];

		foreach ($rating as $data)
		{
			foreach ($data as $key => $v)
			{
				if (strpos($key, 'col_') !== false)
				{
					$c = (int) str_replace('col_', '', $key);

					if (!in_array($c, $compId))
						$compId[] = $c;
				}
			}
		}

		/** @var External\CompetitionsTable[] $competitions */
		$competitions = [];

		if (count($compId))
		{
			$items = External\CompetitionsTable::query()
				->setOrder(['BeginDate' => 'DESC'])
				->setSelect(['id', 'CompName', 'BeginDate'])
				->setFilter([
					'=id' => $compId,
				])
				->exec();

			/** @var External\CompetitionsTable $item */
			foreach ($items as $item)
				$competitions[$item->id] = $item;
		}

		foreach ($rating as $data)
		{
			$row = [
				'place' => (int) $data['num'],
				'name' => $data['fio'],
				'rating' => $data['r'],
				'group' => $data['g'],
				'op' => $data['r'],
				'competitions' => []
			];

			foreach ($data as $key => $v)
			{
				if (!is_string($v))
					continue;

				if (strpos($key, 'col_') !== false)
				{
					$c = (int) str_replace('col_', '', $key);
					$v = explode('/', $v);

					if (!isset($competitions[$c]))
						continue;

					$row['competitions'][] = [
						'title' => $competitions[$c]['CompName'] ?? '',
						'date' => $competitions[$c]['BeginDate']->format('c'),
						'or' => (float) str_replace(',', '.', trim($v[1] ?? '')),
					];
				}
			}

			$result['items'][] = $row;
		}

		return $result;
	}
}