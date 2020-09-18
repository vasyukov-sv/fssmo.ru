<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2019 Olympia.Digital
 */

namespace Olympia\Bitrix\Orm;

use Bitrix\Main\ORM;

class Query extends ORM\Query\Query
{
	public function exec ()
	{
		$this->is_executing = true;

		$dataClass = $this->getEntity()->getDataClass();

		if (method_exists($dataClass, 'normalizeQuery'))
		{
			$fields = [
				'select' => $this->getSelect(),
				'filter' => $this->getFilter(),
			];

			$fields = $dataClass::normalizeQuery($fields);

			$this->setSelect($fields['select']);
			$this->setFilter($fields['filter']);
		}

		$query = $this->buildQuery();

		$cacheId = '';
		$ttl = 0;
		$result = null;

		if ($this->cacheTtl > 0 && (empty($this->join_map) || $this->cacheJoins == true))
			$ttl = $this->entity->getCacheTtl($this->cacheTtl);

		if ($ttl > 0)
		{
			$cacheId = md5($query);
			$result = $this->entity->readFromCache($ttl, $cacheId, $this->countTotal);
		}

		if ($result === null)
		{
			$result = $this->query($query);

			if ($ttl > 0)
				$result = $this->entity->writeToCache($result, $cacheId, $this->countTotal);
		}

		$this->is_executing = false;

		return new Result($this, $result);
	}
}