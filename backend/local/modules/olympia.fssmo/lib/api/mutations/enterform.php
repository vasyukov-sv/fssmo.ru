<?php

namespace Olympia\Fssmo\Api\Mutations;

use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\UserTable;
use Olympia\Bitrix\Helpers;
use Olympia\Fssmo;
use Olympia\Fssmo\Model\EnterTable;
use Olympia\Fssmo\Db\External\ClubsTable;
use Olympia\Fssmo\User;

class EnterForm
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$value, $args, $context)
	{
		Loader::includeModule('iblock');

		$props = $args['data'];

		foreach ($props as $code => $value)
		{
			if (!is_array($value))
				$props[$code] = trim(htmlspecialchars(addslashes($value)));
		}

		if (!$context['user'])
		{
			$isExist = UserTable::query()
				->setSelect(['ID'])
				->setFilter(['=LOGIN' => $props['email']])
				->exec()->fetch();

			if ($isExist)
				throw new Fssmo\Api\Exception('На данный Email адрес уже зарегистриван личный кабинет');

			$name = explode(' ', $props['name']);
			$name = array_map('trim', $name);

			$userId = User::create([
				'LOGIN' => $props['email'],
				'EMAIL' => $props['email'],
				'NAME' => $name[1] ?? '',
				'LAST_NAME' => $name[0] ?? '',
				'PERSONAL_PHONE' => $props['phone'] ?? '',
				'UF_CLUB_ID' => 1,
				'UF_DIGIT_ID' => 1,
			], true);

			global $USER;

			$USER->Authorize($userId);

			$context['user'] = $userId;
		}

		$paymentType = $args['payment'] ?? 'online';

		$orderId = null;

		if ($paymentType == 'online' || $paymentType == 'balance')
		{
			$orderId = Fssmo\Club::createOrder(
				$context['user'],
				false,
				$paymentType == 'balance'
			);
		}

		$club = ClubsTable::query()
			->setSelect(['id', 'ClubName'])
			->setFilter(['=id' => $props['result_club']])
			->exec()->fetch();

		$requestId = EnterTable::add([
			'IBLOCK_ID' => IBLOCK_FORM_ENTER,
			'ACTIVE' => 'Y',
			'NAME' => 'Новый запрос',
			'ACTIVE_FROM' => date('d.m.Y H:i:s'),
			'PROPERTY' => [
				'NAME' => $props['name'],
				'EMAIL' => $props['email'],
				'PHONE' => $props['phone'],
				'PASSPORT' => $props['passport'],
				'PASSPORT_ISSUE_PLACE' => $props['passport_issue_place'],
				'PASSPORT_ISSUE_DATE' => $props['passport_issue_date'],
				'REGISTRATION_ADDRESS' => $props['registration_address'],
				'BIRTHDAY' => $props['birthday'],
				'SPORT_TITLE' => $props['sport_title'],
				'SPORT_TITLE_DATE' => $props['sport_title_date'],
				'FIRST_TRAINER_NAME' => $props['first_trainer_name'],
				'CURRENT_TRAINER_NAME' => $props['current_trainer_name'],
				'ORDER_ID' => $orderId,
			]
		]);

		if (!$orderId)
		{
			$fields = Helpers::getFieldsFromIblock($requestId, IBLOCK_FORM_ENTER);

			Event::send([
				'EVENT_NAME' => 'FORM_ENTER',
				'LID' => 's1',
				'DUPLICATE' => 'N',
				'C_FIELDS' => $fields,
				'LANGUAGE_ID' => LANGUAGE_ID,
			]);
		}

		return [
			'success' => true,
			'order' => $orderId > 0 ? [
				'id' => $orderId,
				'payment' => Fssmo\Competition\Order::getPaymentData($orderId),
			] : null,
		];
	}
}