<?php

namespace Olympia\Fssmo;

use Bitrix\Iblock\PropertyIndex\Storage;
use Bitrix\Main\Loader;

include_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/catalog.smart.filter/class.php');

class Filter extends \CBitrixCatalogSmartFilter
{
	protected $_filter = [];

	public function addParams ($params = [])
	{
		$this->arParams = $this->onPrepareComponentParams(array_merge([
				'SECTION_ID' => 0,
				'PRICE_CODE' => [],
				'SAVE_IN_SESSION' => 'N',
				'CACHE_GROUPS' => 'N',
				'INSTANT_RELOAD' => 'N',
				'SECTION_TITLE' => '',
				'SECTION_DESCRIPTION' => '',
				'FILTER_NAME' => 'arrFilter',
				'CACHE_TIME' => 36000000,
				'CONVERT_CURRENCY' => 'N',
				'CURRENCY_ID' => ''
			],
			$params
		));
	}

	public function executeComponent ()
	{
		$this->IBLOCK_ID = $this->arParams["IBLOCK_ID"];
		$this->SECTION_ID = $this->arParams["SECTION_ID"];
		$this->FILTER_NAME = $this->arParams["FILTER_NAME"];
		$this->SAFE_FILTER_NAME = htmlspecialcharsbx($this->FILTER_NAME);

		if (self::$iblockIncluded === null)
			self::$iblockIncluded = Loader::includeModule('iblock');
		if (!self::$iblockIncluded)
			return '';

		$this->facet = new \Bitrix\Iblock\PropertyIndex\Facet($this->IBLOCK_ID);

		return true;
	}

	public function setFilter (array $filter)
	{
		$this->_filter = $filter;
	}

	public function loadItems ()
	{
		$arResult = [];
		$arResult['ITEMS'] = $this->getResultItems();

		$elementDictionary = [];
		$sectionDictionary = [];
		$dictionaryID = [];

		if (!$this->facet->isValid())
		{
			$index = \Bitrix\Iblock\PropertyIndex\Manager::createIndexer($this->IBLOCK_ID);
			$index->startIndex();
			$index->continueIndex();
			$index->endIndex();
		}

		$this->facet->setSectionId($this->SECTION_ID);
		$res = $this->facet->query($this->_filter);

		$tmpProperty = [];

		while ($rowData = $res->fetch())
		{
			$facetId = $rowData['FACET_ID'];

			if (Storage::isPropertyId($facetId))
			{
				$PID = Storage::facetIdToPropertyId($facetId);

				$rowData['PID'] = $PID;
				$tmpProperty[] = $rowData;

				$item = $arResult['ITEMS'][$PID];

				if ($item['PROPERTY_TYPE'] == 'S')
					$dictionaryID[] = $rowData['VALUE'];

				if ($item['PROPERTY_TYPE'] == 'E')
					$elementDictionary[] = $rowData['VALUE'];

				if ($item["PROPERTY_TYPE"] == "G" && $item['USER_TYPE'] == '')
					$sectionDictionary[] = $rowData['VALUE'];
			}
		}

		$this->predictIBElementFetch($elementDictionary);
		$this->predictIBSectionFetch($sectionDictionary);
		$this->processProperties($arResult, $tmpProperty, $dictionaryID, []);

		foreach($arResult["ITEMS"] as $PID => $arItem)
			uasort($arResult["ITEMS"][$PID]["VALUES"], array($this, "_sort"));

		$items = $arResult["ITEMS"];
		unset($arResult);

		$this->arResult["ITEMS"] = &$items;
	}

	public function parse ($filter)
	{
		$items = &$this->arResult["ITEMS"];

		$facetIndex = array();

		foreach ($items as $PID => $arItem)
		{
			foreach ($arItem["VALUES"] as $key => $ar)
			{
				if (isset($ar["FACET_VALUE"]))
					$facetIndex[$PID][$ar["FACET_VALUE"]] = &$items[$PID]["VALUES"][$key];

				if (
					isset($filter[$ar["CONTROL_NAME"]])
					|| (
						isset($filter[$ar["CONTROL_NAME_ALT"]])
						&& $filter[$ar["CONTROL_NAME_ALT"]] == $ar["HTML_VALUE_ALT"]
					)
				)
				{
					if ($arItem["PROPERTY.TYPE"] == "N")
					{
						$items[$PID]["VALUES"][$key]["HTML_VALUE"] = htmlspecialcharsbx($filter[$ar["CONTROL_NAME"]]);
						$items[$PID]["DISPLAY_EXPANDED"] = "Y";

						if (strlen($filter[$ar["CONTROL_NAME"]]) > 0)
						{
							if ($key == "MIN")
								$this->facet->addNumericPropertyFilter($PID, ">=", $filter[$ar["CONTROL_NAME"]]);
							elseif ($key == "MAX")
								$this->facet->addNumericPropertyFilter($PID, "<=", $filter[$ar["CONTROL_NAME"]]);
						}
					}
					elseif ($arItem["DISPLAY_TYPE"] == "U")
					{
						$items[$PID]["VALUES"][$key]["HTML_VALUE"] = htmlspecialcharsbx($filter[$ar["CONTROL_NAME"]]);
						$items[$PID]["DISPLAY_EXPANDED"] = "Y";

						if (strlen($filter[$ar["CONTROL_NAME"]]) > 0)
						{
							if ($key == "MIN")
								$this->facet->addDatetimePropertyFilter($PID, ">=", MakeTimeStamp($filter[$ar["CONTROL_NAME"]], FORMAT_DATE));
							elseif ($key == "MAX")
								$this->facet->addDatetimePropertyFilter($PID, "<=", MakeTimeStamp($filter[$ar["CONTROL_NAME"]], FORMAT_DATE) + 23*3600+59*60+59);
						}
					}
					elseif ($filter[$ar["CONTROL_NAME"]] == $ar["HTML_VALUE"])
					{
						$items[$PID]["VALUES"][$key]["CHECKED"] = true;
						$items[$PID]["DISPLAY_EXPANDED"] = "Y";

						if ($arItem["USER_TYPE"] === "DateTime")
							$this->facet->addDatetimePropertyFilter($PID, "=", MakeTimeStamp($ar["VALUE"], FORMAT_DATE));
						else
							$this->facet->addDictionaryPropertyFilter($PID, "=", $ar["FACET_VALUE"]);
					}
					elseif ($filter[$ar["CONTROL_NAME_ALT"]] == $ar["HTML_VALUE_ALT"])
					{
						$items[$PID]["VALUES"][$key]["CHECKED"] = true;
						$items[$PID]["DISPLAY_EXPANDED"] = "Y";

						$this->facet->addDictionaryPropertyFilter($PID, "=", $ar["FACET_VALUE"]);
					}
				}
			}
		}

		if ($filter)
		{
			if (!$this->facet->isEmptyWhere())
			{
				foreach ($items as $PID => &$arItem)
				{
					if ($arItem["PROPERTY_TYPE"] != "N")
					{
						foreach ($arItem["VALUES"] as $key => &$arValue)
						{
							$arValue["DISABLED"] = true;
							$arValue["ELEMENT_COUNT"] = 0;
						}
						unset($arValue);
					}
				}
				unset($arItem);

				$res = $this->facet->query($this->_filter);

				while ($row = $res->fetch())
				{
					$facetId = $row["FACET_ID"];

					if (Storage::isPropertyId($facetId))
					{
						$pp = Storage::facetIdToPropertyId($facetId);

						if ($items[$pp]["PROPERTY_TYPE"] == "N")
						{
							if (is_array($items[$pp]["VALUES"]))
							{
								$items[$pp]["VALUES"]["MIN"]["FILTERED_VALUE"] = $row["MIN_VALUE_NUM"];
								$items[$pp]["VALUES"]["MAX"]["FILTERED_VALUE"] = $row["MAX_VALUE_NUM"];
							}
						}
						else
						{
							if (isset($facetIndex[$pp][$row["VALUE"]]))
							{
								unset($facetIndex[$pp][$row["VALUE"]]["DISABLED"]);
								$facetIndex[$pp][$row["VALUE"]]["ELEMENT_COUNT"] = $row["ELEMENT_COUNT"];
							}
						}
					}
				}
			}
		}

		$resultFilter = [];

		$filter = [];

		foreach ($items as $PID => $arItem)
		{
			if ($arItem["PROPERTY_TYPE"] == "N")
			{
				$existMinValue = (strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0);
				$existMaxValue = (strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0);

				if ($existMinValue || $existMaxValue)
				{
					$filterKey = '';
					$filterValue = '';

					if ($existMinValue && $existMaxValue)
					{
						$filterKey = "><PROPERTY.".$arItem['CODE'];
						$filterValue = array($arItem["VALUES"]["MIN"]["HTML_VALUE"], $arItem["VALUES"]["MAX"]["HTML_VALUE"]);
					}
					elseif ($existMinValue)
					{
						$filterKey = ">=PROPERTY.".$arItem['CODE'];
						$filterValue = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
					}
					elseif ($existMaxValue)
					{
						$filterKey = "<=PROPERTY.".$arItem['CODE'];
						$filterValue = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
					}

					$resultFilter[$filterKey] = $filterValue;
				}
			}
			elseif ($arItem["DISPLAY_TYPE"] == "U")
			{
				$existMinValue = (strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0);
				$existMaxValue = (strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0);

				if ($existMinValue || $existMaxValue)
				{
					$filterKey = '';
					$filterValue = '';

					if ($existMinValue && $existMaxValue)
					{
						$filterKey = "><PROPERTY.".$arItem['CODE'];
						$timestamp1 = MakeTimeStamp($arItem["VALUES"]["MIN"]["HTML_VALUE"], FORMAT_DATE);
						$timestamp2 = MakeTimeStamp($arItem["VALUES"]["MAX"]["HTML_VALUE"], FORMAT_DATE);

						if ($timestamp1 && $timestamp2)
							$filterValue = array(FormatDate("Y-m-d H:i:s", $timestamp1), FormatDate("Y-m-d H:i:s", $timestamp2 + 23*3600+59*60+59));
					}
					elseif ($existMinValue)
					{
						$filterKey = ">=PROPERTY.".$arItem['CODE'];
						$timestamp1 = MakeTimeStamp($arItem["VALUES"]["MIN"]["HTML_VALUE"], FORMAT_DATE);

						if ($timestamp1)
							$filterValue = FormatDate("Y-m-d H:i:s", $timestamp1);
					}
					elseif ($existMaxValue)
					{
						$filterKey = "<=PROPERTY.".$arItem['CODE'];
						$timestamp2 = MakeTimeStamp($arItem["VALUES"]["MAX"]["HTML_VALUE"], FORMAT_DATE);

						if ($timestamp2)
							$filterValue = FormatDate("Y-m-d H:i:s", $timestamp2 + 23*3600+59*60+59);
					}

					$resultFilter[$filterKey] = $filterValue;
				}
			}
			elseif ($arItem["USER_TYPE"] == "DateTime")
			{
				$datetimeFilters = array();

				foreach ($arItem["VALUES"] as $key => $ar)
				{
					if ($ar["CHECKED"])
					{
						$filterKey = "><PROPERTY.".$arItem['CODE'];
						$timestamp = MakeTimeStamp($ar["VALUE"], FORMAT_DATE);
						$filterValue = array(
							FormatDate("Y-m-d H:i:s", $timestamp),
							FormatDate("Y-m-d H:i:s", $timestamp + 23 * 3600 + 59 * 60 + 59)
						);

						$datetimeFilters[] = array($filterKey => $filterValue);
					}
				}

				if ($datetimeFilters)
				{
					$datetimeFilters["LOGIC"] = "OR";
					$resultFilter[] = $datetimeFilters;
				}
			}
			else
			{
				foreach ($arItem["VALUES"] as $key => $ar)
				{
					if ($ar["CHECKED"])
					{
						$filterKey = "=PROPERTY.".$arItem['CODE'];
						$backKey = htmlspecialcharsback($key);

						if (!isset($resultFilter[$filterKey]))
							$resultFilter[$filterKey] = array($backKey);
						elseif (!is_array($resultFilter[$filterKey]))
							$resultFilter[$filterKey] = array($filter[$filterKey], $backKey);
						elseif (!in_array($backKey, $resultFilter[$filterKey]))
							$resultFilter[$filterKey][] = $backKey;
					}
				}
			}
		}

		return $resultFilter;
	}

	public function getItems ()
	{
		$result = [];

		foreach ($this->arResult["ITEMS"] as $item)
		{
			if (count($item['VALUES']) <= 1)
				continue;

			$row = [
				'id' => (int) $item['ID'],
				'code' => (string) $item['CODE'],
				'title' => (string) $item['NAME'],
				'type' => (string) $item['PROPERTY_TYPE'],
				'values' => []
			];

			foreach ($item['VALUES'] as $value)
			{
				$row['values'][] = [
					'id' => (string) $value['CONTROL_NAME'],
					'title' => (string) $value['VALUE'],
					'checked' => $value['CHECKED'] ?? false,
					'disabled' => $value['DISABLED'] ?? false,
				];
			}

			$result[] = $row;
		}

		return $result;
	}
}