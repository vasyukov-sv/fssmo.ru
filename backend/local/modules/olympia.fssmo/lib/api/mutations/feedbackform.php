<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;
use Olympia\Bitrix\Helpers;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Model\FeedbackTable;

class FeedbackForm
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args)
	{
		Loader::includeModule('iblock');

		$fields = [
			'name'		=> ['req' => true, 'name' => 'Имя'],
			'email'		=> ['req' => true, 'name' => 'Email'],
			'text'		=> ['req' => true, 'name' => 'Сообщение'],
		];

		$props = $args['data'];

		foreach ($props as $code => $value)
		{
			if (!is_array($value))
				$props[$code] = trim(htmlspecialchars(addslashes($value)));
		}

		foreach ($fields as $key => $data)
		{
			if ($data['req'] && (!isset($props[$key]) || $props[$key] == ''))
				throw new Exception('Заполните обязательное поле "'.$data['name'].'"');
		}

		$requestId = FeedbackTable::add([
			'IBLOCK_ID' => IBLOCK_FORM_FEEDBACK,
			'ACTIVE' => 'Y',
			'NAME' => 'Новый запрос',
			'ACTIVE_FROM' => date('d.m.Y H:i:s'),
			'PROPERTY' => [
				'NAME'	=> $props['name'],
				'EMAIL'	=> $props['email'],
				'TEXT'	=> $props['text'],
			]
		]);

		$fields = Helpers::getFieldsFromIblock($requestId, IBLOCK_FORM_FEEDBACK);

		Event::send([
			'EVENT_NAME' => 'FORM_FEEDBACK',
			'LID' => SITE_ID,
			'DUPLICATE' => 'N',
			'C_FIELDS' => $fields,
			'LANGUAGE_ID' => LANGUAGE_ID
		]);

		return true;
	}
}