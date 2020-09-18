<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Iblock\InheritedProperty\SectionValues;
use Bitrix\Iblock\Model\Section;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use CFile;
use Olympia\Bitrix\Helpers;
use Olympia\Fssmo\Model\PageTable;

class Page
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		$uri = $args['url'];

		if (substr($uri, -1, 1) !== '/')
			$uri .= '/';

		$parts = explode('/', rtrim($uri, '/'));

		$meta = [
			'og:type' => 'website',
			'og:image' => Helpers::getHttpHost().'/images/share.png',
			'og:image:width' => 1024,
			'og:image:height' => 480,
			'og:url' => Helpers::getHttpHost().$uri,
		];

		$breadcrumbs = [];
		$title = '';

		$cache = Cache::createInstance();
		$cacheId = 'PageTree';
		$cachePath = 'graphql/page';

		if ($cache->initCache(36000, $cacheId, $cachePath))
			$tree = $cache->getVars();
		else
		{
			/** @var DataManager $entity */
			$entity = Section::compileEntityByIblock(IBLOCK_STRUCTURE);

			$elements = [];

			$sectionItems = $entity::query()
				->setSelect(['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'DESCRIPTION'])
				->setFilter(['=ACTIVE' => 'Y'])
				->exec();

			foreach ($sectionItems as $item)
			{
				$item['~ID'] = (int) $item['ID'];
				$item['ID'] = 'S'.(int) $item['ID'];
				$item['TYPE'] = 'SECTION';
				$item['IBLOCK_SECTION_ID'] = 'S'.(int) $item['IBLOCK_SECTION_ID'];

				$elements[] = $item;
			}

			$elementItems = PageTable::query()
				->setSelect(['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'DETAIL_TEXT', 'PROPERTY.IBLOCK_ID'])
				->setFilter(['=ACTIVE' => 'Y'])
				->exec();

			/** @var PageTable $item */
			foreach ($elementItems as $item)
			{
				$item = $item->toArray();

				$item['~ID'] = (int) $item['ID'];
				$item['ID'] = 'E'.(int) $item['ID'];
				$item['TYPE'] = 'ELEMENT';
				$item['IBLOCK_SECTION_ID'] = 'S'.(int) $item['IBLOCK_SECTION_ID'];

				$elements[] = $item;
			}

			/**
			 * @param $items
			 * @param string $parentId
			 * @param string $uri
			 * @return array
			 */
			function _recursiveTree ($items, $parentId = 'S0', $uri = '/')
			{
				$result = [];

				foreach ($items as $item)
				{
					if ($item['IBLOCK_SECTION_ID'] === $parentId)
					{
						$url = $uri.$item['CODE'].'/';

						$result[$url] = $item;

						$result = array_merge($result, _recursiveTree($items, $item['ID'], $url));
					}
				}

				return $result;
			}

			$tree = _recursiveTree($elements);

			$tag = Application::getInstance()->getTaggedCache();
			$tag->startTagCache($cachePath);
			$tag->registerTag('iblock_id_'.IBLOCK_STRUCTURE);
			$tag->endTagCache();

			$cache->startDataCache();
			$cache->endDataCache($tree);
		}

		$element = null;
		$tmpUri = '/';

		foreach ($parts as $i => $part)
		{
			if ($part === '')
				$element = $tree['/index/'];
			else
			{
				$tmpUri .= $part.'/';

				if (isset($tree[$tmpUri.'index/']))
					$element = $tree[$tmpUri.'index/'];
				elseif (isset($tree[$tmpUri]))
					$element = $tree[$tmpUri];
				else
				{
					if ($element && $element['TYPE'] === 'ELEMENT' && $element['PROPERTY_IBLOCK_ID'] > 0)
					{
						$obj = ElementTable::query()
							->setSelect(['ID', 'NAME'])
							->setFilter(['=CODE' => $part])
							->exec()->fetch();

						if (!$obj)
							break;

						$tree[$tmpUri] = $element = [
							'ID' => $obj['ID'],
							'~ID' => $obj['ID'],
							'NAME' => $obj['NAME'],
							'TYPE' => 'ELEMENT',
						];

						break;
					}
					else
						break;
				}
			}
		}

		$breadcrumbsParts = ['/index/'];

		$lastUrlPath = '/';

		foreach ($parts as $i => $part)
		{
			if ($part === '')
				continue;

			$lastUrlPath .= $part.'/';

			$breadcrumbsParts[] = $lastUrlPath;
		}

		foreach ($breadcrumbsParts as $url)
		{
			if (!isset($tree[$url]))
				continue;

			$breadcrumbs[] = [
				'title' => $tree[$url]['NAME'],
				'url' => str_replace('/index', '', $url),
			];
		}

		unset($breadcrumbsParts);

		$text = '';

		if ($element)
		{
			if ($element['TYPE'] == 'ELEMENT')
			{
				$ipropValues = new ElementValues(IBLOCK_STRUCTURE, $element['~ID']);
				$metaValues = $ipropValues->getValues();

				if (isset($metaValues['ELEMENT_PAGE_TITLE']))
				{
					$meta['og:title'] = $metaValues['ELEMENT_PAGE_TITLE'];
					$element['NAME'] = $metaValues['ELEMENT_PAGE_TITLE'];
				}
				elseif (isset($metaValues['ELEMENT_META_TITLE']))
				{
					$meta['og:title'] = $metaValues['ELEMENT_META_TITLE'];
					$element['NAME'] = $metaValues['ELEMENT_META_TITLE'];
				}
				else
					$meta['og:title'] = $element['NAME'];

				if (isset($metaValues['ELEMENT_META_TITLE']))
					$meta['title'] = $metaValues['ELEMENT_META_TITLE'];

				if (isset($metaValues['ELEMENT_META_KEYWORDS']))
					$meta['keywords'] = $metaValues['ELEMENT_META_KEYWORDS'];

				if (isset($metaValues['ELEMENT_META_DESCRIPTION']))
					$meta['description'] = $metaValues['ELEMENT_META_DESCRIPTION'];

				if (isset($metaValues['ELEMENT_META_KEYWORDS']))
					$meta['og:description'] = $metaValues['ELEMENT_META_DESCRIPTION'];

				$title = $element['NAME'];
				$text = $element['DETAIL_TEXT'];
			}
			elseif ($element['TYPE'] == 'SECTION')
			{
				$ipropValues = new SectionValues(IBLOCK_STRUCTURE, $element['~ID']);
				$metaValues = $ipropValues->getValues();

				if (isset($metaValues['SECTION_PAGE_TITLE']))
				{
					$meta['og:title'] = $metaValues['SECTION_PAGE_TITLE'];
					$element['NAME'] = $metaValues['SECTION_PAGE_TITLE'];
				}
				elseif (isset($metaValues['SECTION_META_TITLE']))
				{
					$meta['og:title'] = $metaValues['SECTION_META_TITLE'];
					$element['NAME'] = $metaValues['SECTION_META_TITLE'];
				}
				else
					$meta['og:title'] = $element['NAME'];

				if (isset($metaValues['SECTION_META_TITLE']))
					$meta['title'] = $metaValues['SECTION_META_TITLE'];

				if (isset($metaValues['SECTION_META_KEYWORDS']))
					$meta['keywords'] = $metaValues['SECTION_META_KEYWORDS'];

				if (isset($metaValues['SECTION_META_DESCRIPTION']))
					$meta['description'] = $metaValues['SECTION_META_DESCRIPTION'];

				if (isset($metaValues['SECTION_META_KEYWORDS']))
					$meta['og:description'] = $metaValues['SECTION_META_DESCRIPTION'];

				$title = $element['NAME'];
				$text = $element['DESCRIPTION'];
			}
		}

		$parsedMeta = [];

		foreach ($meta as $k => $v)
		{
			$parsedMeta[] = [
				'name' => $k, 'content' => $v
			];
		}

		Loader::includeModule('highloadblock');

		$hlblock = HighloadBlockTable::getList([
			'filter' => ['=NAME' => 'OlympiaVisualEditor']
		])->fetch();

		$valuesClass = _hl($hlblock['ID']);

		$items = $valuesClass::getList([
			'filter' => ['=UF_LANG' => LANGUAGE_ID]
		]);

		$area = [];

		foreach ($items as $item)
		{
			//if (!isset($area[$item['UF_FIELD_ID']]))
			//	$area[$item['UF_FIELD_ID']] = [];

			if ($item['UF_TYPE'] == 'FILE')
			{
				$file = CFile::GetFileArray($item['UF_VALUE']);

				if ($file)
				{
					$item['UF_VALUE'] = [
						'src' => $file['SRC'],
						'name' => $file['DESCRIPTION'] != '' ? $file['DESCRIPTION'] : GetFileNameWithoutExtension($file['ORIGINAL_NAME']),
						'ext' => GetFileExtension($file['SRC']),
						'size' => CFile::FormatSize($file['FILE_SIZE'], 1)
					];
				}
			}

			if (isset($area[$item['UF_FIELD_ID']]))
				$area[$item['UF_FIELD_ID']] = [$area[$item['UF_FIELD_ID']]];
			else
				$area[$item['UF_FIELD_ID']] = $item['UF_VALUE'];
		}

		return [
			'id' => $element['ID'],
			'url' => $uri,
			'title' => $title,
			'meta' => $parsedMeta,
			'breadcrumbs' => $breadcrumbs,
			'text' => $text,
			'area' => $area,
		];
	}
}