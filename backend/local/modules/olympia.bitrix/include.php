<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

/**
 * @param $id
 * @return \Bitrix\Main\Entity\DataManager
 * @throws Exception
 * @throws \Bitrix\Main\LoaderException
 * @throws \Bitrix\Main\SystemException
 */
function _hl ($id)
{
	if (!Loader::includeModule('highloadblock'))
		throw new Exception('highloadblock module not loaded');

	static $_hl = [];

	$entity = null;

	if (isset($_hl[$id]))
		return $_hl[$id];

	$entity = HighloadBlockTable::getById($id)->fetch();

	if (!$entity)
		throw new Exception('hlb entity '.$id.' not found');

	$_hl[$id] = HighloadBlockTable::compileEntity($entity)->getDataClass();

	return $_hl[$id];
}

function p($v)
{
	global $USER;

	if ($USER && $USER->IsAdmin())
	{
		echo '<pre>';
		print_r($v);
		echo '</pre>';
	}
}