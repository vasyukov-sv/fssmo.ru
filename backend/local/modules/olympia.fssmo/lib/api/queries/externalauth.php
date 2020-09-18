<?php

namespace Olympia\Fssmo\Api\Queries;

use Bitrix\Main\Loader;
use CSocServAuthManager;
use Olympia\Bitrix\Helpers;

class ExternalAuth
{
	public static function resolve (/** @noinspection PhpUnusedParameterInspection */$root, $args)
	{
		Loader::includeModule('socialservices');

		$authManager = new CSocServAuthManager();

		$services = $authManager->GetActiveAuthServices([
			'BACKURL' => isset($args['back_url']) ? $args['back_url'] : '/'
		]);

		$items = [];

		foreach ($services as $service)
		{
			$link = preg_replace('/BX.util.popup\(\'(.*?)\',.*?\)/', '$1', $service['ONCLICK']);

			if ($service['ID'] === 'Facebook')
				$link = str_replace(',user_friends', '', $link);
			if ($service['ID'] === 'VKontakte')
			{
				$link = preg_replace("/redirect_uri=(.*?)%3Fauth_service_id%3DVKontakte&scope/i", "redirect_uri=".urlencode(Helpers::getHttpHost().'/local/tools/oauth/vkontakte.php')."%3Fauth_service_id%3DVKontakte&scope", $link);
				$link = str_replace('friends,offline,', '', $link);
			}

			$items[] = [
				'id' => mb_strtolower($service['ID']),
				'name' => $service['NAME'],
				'link' => $link,
			];
		}

		return $items;
	}
}