<?php

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix;

class Handlers
{
	public static function deleteKernelScripts (&$content)
	{
		global $USER;

		if (defined("ADMIN_SECTION"))
			return;

		if (is_object($USER) && $USER->IsAuthorized())
		{
			$arPatternsToRemove = [
				'/<script[^>]+?>var _ba = _ba[^<]+<\/script>/',
			];
		}
		else
		{
			$arPatternsToRemove = Array
			(
				'/<script.+?src=".+?js\/main\/core\/.+?(\.min|)\.js\?\d+"><\/script\>/',
				'/<link.+?href="\/bitrix\/js\/.+?(\.min|)\.css\?\d+".+?>/',
				'/<link.+?href="\/bitrix\/components\/.+?(\.min|)\.css\?\d+".+?>/',
				'/<script.+?src=".+?kernel_main\/kernel_main(\.min|)\.js\?\d+"><\/script\>/',
				'/<link.+?href=".+?kernel_main\/kernel_main(\.min|)\.css\?\d+"[^>]+>/',
				'/<link.+?href=".+?main\/popup(\.min|)\.css\?\d+"[^>]+>/',
				'/<script.+?>BX\.(setCSSList|setJSList)\(\[.+?\]\).*?<\/script>/',
				'/<script.+?>if\(\!window\.BX\)window\.BX.+?<\/script>/',
				'/<script[^>]+?>\(window\.BX\|\|top\.BX\)\.message[^<]+<\/script>/',
				'/<script[^>]+?>var _ba = _ba[^<]+<\/script>/',
				'/<script[^>]+?>.+?bx-core.*?<\/script>/'
			);
		}

		$content = preg_replace($arPatternsToRemove, "", $content);
		$content = preg_replace("/\n{2,}/", "\n", $content);
	}
}