<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2019 Olympia.Digital
 */

namespace Olympia\Bitrix\Orm;

use Bitrix\Main\DB\ResultIterator;
use Bitrix\Main\ORM;
use Bitrix\Main\Text\Converter;

class Result extends ORM\Query\Result
{
	public function fetch (Converter $converter = null)
	{
		/** @var $dataClass Model */
		$dataClass = $this->query->getEntity()->getDataClass();

		$row = $this->result->fetch($converter);

		if (!$row)
			return false;

		if (method_exists($dataClass, 'fetchDataModifier'))
			$row = $dataClass::fetchDataModifier($row);

		$refs = [];
		$refs_class = [];

		foreach ($this->query->getSelectChains() as $selectChain)
		{
			$iterableElements = array_slice($selectChain->getAllElements(), 1);

			foreach ($iterableElements as $element)
			{
				/** @var $element ORM\Query\ChainElement $field */
				$field = $element->getValue();

				if ($field instanceof ORM\Fields\Relations\Reference)
				{
					$parts = $selectChain->getDefinitionParts();

					if (count($parts) <= 1)
						continue;

					$p = &$refs;

					foreach ($parts as $i => $part)
					{
						if ($i === count($parts) - 1)
						{
							$p[$part] = $selectChain->getAlias();
							continue;
						}

						if ($i === count($parts) - 2)
							$refs_class[$part] = $field->getRefEntity()->getDataClass();

						if (!isset($p[$part]))
							$p[$part] = [];

						$p = &$p[$part];
					}
				}

				if ($field instanceof ORM\Fields\IReadable)
					$row[$selectChain->getAlias()] = $field->cast($row[$selectChain->getAlias()]);
			}
		}

		$row += $this->__ref($refs, $refs_class, $row);

		return $dataClass::cloneResultMap($row);
	}

	public function fetchAll (Converter $converter = null)
	{
		$res = [];

		while ($ar = $this->fetch($converter))
			$res[] = $ar;

		return $res;
	}

	public function getIterator ()
	{
		return new ResultIterator($this);
	}

	private function __ref ($refs, $class, &$row)
	{
		$result = [];

		foreach ($refs as $ref => $refData)
		{
			if (!is_subclass_of($class[$ref], __NAMESPACE__.'\Model'))
				continue;

			$result[$ref] = [];

			$tmp = [];

			foreach ($refData as $key => $value)
			{
				if (is_array($value))
					$tmp += $this->__ref([$key => $value], $class, $row);
				else
				{
					$tmp[$key] = $row[$value];
					unset($row[$value]);
				}
			}

			/** @noinspection PhpUndefinedMethodInspection */
			$result[$ref] = $class[$ref]::cloneResultMap($tmp);
		}

		return $result;
	}
}