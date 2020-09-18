<?

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>, https://github.com/alexprowars
 * @copyright 2017 Olympia.Digital
 */

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaySystem;

define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define("DisableEventsCheck", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION;

$log = fopen($_SERVER['DOCUMENT_ROOT'].'/local/logs/payments_'.date('d.m.Y').'.log', 'a+');

fwrite($log, "\n------------------PAYMENT HANDLER----".date("d.m.Y H:i:s")."-----\n");
fwrite($log, file_get_contents('php://input'));
fwrite($log, "\n");
fwrite($log, print_r($_REQUEST, true));
fwrite($log, "\n");
fwrite($log, "\n");

fclose($log);

if (Loader::includeModule("sale"))
{
	$context = Application::getInstance()->getContext();
	$request = $context->getRequest();

	$item = PaySystem\Manager::searchByRequest($request);

	if ($item !== false)
	{
		$service = new PaySystem\Service($item);

		if ($service instanceof PaySystem\Service)
		{
			$result = $service->processRequest($request);

			if (!$result->isSuccess())
			{
				fwrite($log, implode(',', $result->getErrorMessages()));
				fwrite($log, "\n");

				LocalRedirect('/personal/competitions/', true);
			}
		}
	}
}

fclose($log);

$APPLICATION->FinalActions();
die();