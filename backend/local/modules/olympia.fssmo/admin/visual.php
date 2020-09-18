<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

define('BX_PUBLIC_MODE', 0);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/admin_tools.php");
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

$POST_RIGHT = $APPLICATION->GetGroupRight("olympia.fssmo");

if ($POST_RIGHT == 'D')
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));

CJSCore::Init(array('window'));

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");

IncludeModuleLangFile(str_replace('/local/modules/olympia.bitrix/admin/visual_editor.php', '/bitrix/modules/main/public/component_props2.php', __FILE__));

$io = CBXVirtualIo::GetInstance();

$arValues = [];
$arValues['FIELDS'] = $arValues['~FIELDS'] = json_decode(urldecode($_REQUEST['field']), true);
$arValues['USE_LANG'] = 'Y';

if (!is_array($arValues['FIELDS']))
	$arValues['FIELDS'] = [];

$tmp = [];

foreach ($arValues['FIELDS'] as $f)
	$tmp[$f['code']] = $f;

$arValues['FIELDS'] = $tmp;

$_REQUEST['bxsender'] = 'core_window_cauthdialog';

//$obJSPopup = new CJSPopup("lang=".LANGUAGE_ID."&code=".urlencode($arValues['CODE'])."&fields=".urlencode(json_decode($arValues['FIELDS'])));

Loader::includeModule("fileman");
Loader::includeModule("iblock");
Loader::includeModule("highloadblock");

$componentName = 'olympia:visual';

if (!CComponentEngine::CheckComponentName($componentName))
	$strWarning .= GetMessage("comp_prop_error_name")."<br>";

$hlblock = HighloadBlockTable::getList([
	'filter' => ['=NAME' => 'OlympiaVisualEditor']
])->fetch();

$valuesClass = HighloadBlockTable::compileEntity(
	HighloadBlockTable::getById($hlblock['ID'])->fetch()
)->getDataClass();

if (isset($_POST['submitbtn']))
{
	$PROP = [];

	if (isset($_POST['PROP']))
		$PROP = $_POST['PROP'];

	$PROP_del = $_POST['PROP_del'];
	$arFileProps = [];

	foreach ($arValues['FIELDS'] as $code => $value)
	{
		if ($value['type'] == 'FILE' || $value['type'] == 'SLIDER' || $value['type'] == 'SLIDER_PREVIEW')
			$arFileProps[] = $code;
	}

	if (is_array($PROP))
	{
		foreach ($PROP as $k1 => $val1)
		{
			if (is_array($val1))
			{
				foreach ($val1 as $k2 => $val2)
				{
					$text_name = preg_replace("/([^a-z0-9])/is", "_", "PROP[".$k1."][".$k2."][VALUE][TEXT]");

					if (array_key_exists($text_name, $_POST))
					{
						$type_name = preg_replace("/([^a-z0-9])/is", "_", "PROP[".$k1."][".$k2."][VALUE][TYPE]");

						$PROP[$k1][$k2]["VALUE"] = [
							"TEXT" => $_POST[$text_name],
							"TYPE" => $_POST[$type_name],
						];
					}
				}
			}
		}

		foreach ($PROP as $k1 => $val1)
		{
			if (is_array($val1))
			{
				foreach ($val1 as $k2 => $val2)
				{
					if (!is_array($val2))
						$PROP[$k1][$k2] = array("VALUE" => $val2);
				}
			}
		}

		$files = $_FILES["PROP"];

		if (is_array($files))
		{
			if (!is_array($PROP))
				$PROP = array();

			CAllFile::ConvertFilesToPost($_FILES["PROP"], $PROP);
		}

		foreach ($arFileProps as $k1)
		{
			if (isset($PROP_del[$k1]) && is_array($PROP_del[$k1]))
			{
				if (!is_array($PROP[$k1]))
					$PROP[$k1] = array();
				foreach ($PROP_del[$k1] as $prop_value_id => $tmp)
				{
					if (!array_key_exists($prop_value_id, $PROP[$k1]))
						$PROP[$k1][$prop_value_id] = null;
				}
			}

			if (isset($PROP[$k1]) && is_array($PROP[$k1]))
			{
				foreach ($PROP[$k1] as $prop_value_id => $prop_value)
				{
					$PROP[$k1][$prop_value_id] = CIBlock::makeFilePropArray(
						$PROP[$k1][$prop_value_id],
						$PROP_del[$k1][$prop_value_id] === "Y",
						isset($_POST["DESCRIPTION_PROP"][$k1][$prop_value_id])? $_POST["DESCRIPTION_PROP"][$k1][$prop_value_id]: $_POST["PROP_descr"][$k1][$prop_value_id]
					);
				}
			}
		}

		$DESCRIPTION_PROP = $_POST["DESCRIPTION_PROP"];

		if (is_array($DESCRIPTION_PROP))
		{
			foreach($DESCRIPTION_PROP as $k1=>$val1)
			{
				foreach($val1 as $k2=>$val2)
				{
					if(is_set($PROP[$k1], $k2) && is_array($PROP[$k1][$k2]) && is_set($PROP[$k1][$k2], "DESCRIPTION"))
						$PROP[$k1][$k2]["DESCRIPTION"] = $val2;
					else
						$PROP[$k1][$k2] = Array("VALUE"=>$PROP[$k1][$k2], "DESCRIPTION"=>$val2);
				}
			}
		}
	}

	$updateProps = [];

	foreach ($PROP as $code => $values)
	{
		$type = $arValues['FIELDS'][$code]['type'];

		foreach ($values as $id => $value)
		{
			if ($value['VALUE'] == '' && !is_numeric($id))
				continue;

			$updateProps[$code][$id] = $value['VALUE'];

			if ($type == 'HTML')
				$updateProps[$code][$id] = $value['VALUE']['TEXT'];
			elseif ($type == 'FILE' || $type == 'SLIDER' || $type == 'SLIDER_PREVIEW')
				$updateProps[$code][$id] = $value['VALUE'];
		}
	}

	foreach ($updateProps as $code => $values)
	{
		$type = $arValues['FIELDS'][$code]['type'];

		$ids = [];

		foreach ($values as $id => $val)
		{
			if (is_numeric($id))
			{
				$check = $valuesClass::getRow([
					'filter' => [
						'=ID' => (int) $id,
						'=UF_FIELD_ID' => $code
					]
				]);

				if ($type == 'FILE' || $type == 'SLIDER' || $type == 'SLIDER_PREVIEW')
				{
					$val['old_file'] = $check['UF_VALUE'];

					$fileId = CFile::SaveFile($val, 'olympia');

					if ($fileId > 0)
						$val = $fileId;
					else
					{
						if (!isset($val["del"]) || $val["del"] == '')
							$ids[] = $check['ID'];

						continue;
					}
				}

				if ($check && $val != '')
					$ids[] = $check['ID'];

				if ($check)
				{
					$valuesClass::update($check['ID'], [
						'UF_VALUE' => $val,
						'UF_TYPE' => $type
					]);
				}
				else
				{
					$res = $valuesClass::add([
						'UF_FIELD_ID' => $code,
						'UF_VALUE' => $val,
						'UF_LANG' => isset($arValues['USE_LANG']) && $arValues['USE_LANG'] === 'Y' ? LANGUAGE_ID : '',
						'UF_TYPE' => $type
					]);

					if ($res->isSuccess())
						$ids[] = $res->getId();
				}
			}
			else
			{
				if ($type == 'FILE' || $type == 'SLIDER' || $type == 'SLIDER_PREVIEW')
				{
					$fileId = CFile::SaveFile($val, 'olympia');

					if ($fileId > 0)
						$val = $fileId;
					else
						continue;
				}

				if ($val == '')
					continue;

				$res = $valuesClass::add([
					'UF_FIELD_ID' => $code,
					'UF_VALUE' => $val,
					'UF_LANG' => isset($arValues['USE_LANG']) && $arValues['USE_LANG'] === 'Y' ? LANGUAGE_ID : '',
					'UF_TYPE' => $type
				]);

				if ($res->isSuccess())
					$ids[] = $res->getId();
			}
		}

		$deleted = $valuesClass::getList([
			'filter' => [
				'!ID' => $ids,
				'=UF_FIELD_ID' => $code,
				'=UF_LANG' => isset($arValues['USE_LANG']) && $arValues['USE_LANG'] === 'Y' ? LANGUAGE_ID : ''
			]
		]);

		while ($del = $deleted->fetch())
		{
			$valuesClass::delete($del['ID']);
		}
	}

	if(strlen($strWarning) <= 0)
	{
?>
<script>
<?if($_REQUEST['subdialog'] != 'Y'):?>
top.window.location.href = '<?=CUtil::JSEscape($_REQUEST["back_url"])?>';
<?else:?>
if (null != top.structReload)
	top.structReload('<?=urlencode($_REQUEST["path"])?>');
<?endif;?>
</script>
<?
	}
	else
	{
?>
<script>
top.CloseWaitWindow();
top.<?=$obJSPopup->jsPopup?>.ShowError('<?=CUtil::JSEscape($strWarning)?>');
var pMainObj = top.GLOBAL_pMainObj['<?=CUtil::JSEscape($editor_name)?>'];
pMainObj.Show(true);
<?if ($bSessIDRefresh):?>
top.BXSetSessionID('<?=CUtil::JSEscape(bitrix_sessid())?>');
<?endif;?>
</script>
<?
	}
	die();
}

$fieldValues = [];

$items = $valuesClass::getList([
	'filter' => ['=UF_LANG' => isset($arValues['USE_LANG']) && $arValues['USE_LANG'] === 'Y' ? LANGUAGE_ID : '']
]);

while ($item = $items->fetch())
{
	if (!isset($fieldValues[$item['UF_FIELD_ID']]))
		$fieldValues[$item['UF_FIELD_ID']] = [];

	$fieldValues[$item['UF_FIELD_ID']][$item['ID']] = [
		'VALUE' => $item['UF_VALUE']
	];

	if ($arValues['FIELDS'][$item['UF_FIELD_ID']]['type'] == 'HTML')
	{
		$fieldValues[$item['UF_FIELD_ID']][$item['ID']]['VALUE'] = [
			'TEXT' => $item['UF_VALUE'],
			'TYPE' => 'html',
		];
	}
}

//$obJSPopup->StartContent(array());

?>
</form>
<script>

function BXFormSubmit()
{
	ShowWaitWindow();
	var obForm = document.forms.editor_form;
	obForm.elements.submitbtn.click();
}

function BXSetSessionID(new_sessid)
{
	document.forms.editor_form.sessid.value = new_sessid;
}
</script>

<style>
	.adm-workarea {
		padding: 0;
		background: white;
	}

	.bx-core-adm-dialog-content  {
		background: url(/bitrix/panel/main/images/submenu-bg.png) repeat 0 0;
	}
	.bx-core-adm-admin-dialog .bx-core-adm-dialog-content {
		padding: 0;
		flex-grow: 1;
		border: solid 1px #dce7ed;
		border-bottom: 0;
		overflow: auto;
	}

	.bx-core-adm-dialog-buttons {
		padding: 12px;
		bottom: 0;
		border: solid 1px #dce7ed;
	}

	.bx-core-adm-admin-dialog {
		height: 100%;
		display: flex;
		flex-direction: column;
	}

	.adm-detail-content-cell-l {
		text-align: center;
	}

	.adm-detail-content {
		padding: 12px;
	}

	body {
		overflow: hidden;
	}

	.adm-photoeditor-container {
		min-height: 600px;
	}

	.adm-photoeditor-sidebar {
		padding-top: 0
	}

	.popup-window {
		padding: 5px;
	}

	.adm-photoeditor-buttons-panel {
		padding: 5px 0;
		margin-bottom: 10px;
	}

	.adm-photoeditor-sidebar-options {
		margin-bottom: 14px;
	}

	.sidebar-options-checkbox-container label:before, .sidebar-options-checkbox-container label:after {
		height: 5px;
		top: -9px;
	}

	.sidebar-options-checkbox-container label:after {
		top: auto;
		bottom: -12px;
	}

	.popup-window-buttons {
		padding: 10px 0 10px;
	}

	.adm-workarea .adm-input, .adm-workarea input[type="text"], .adm-workarea input[type="password"], .adm-workarea input[type="email"] {
		width: 100%
	}
</style>

<div class="bx-core-adm-admin-dialog">
	<div class="bx-core-adm-dialog-content ">
		<iframe src="javascript:void(0)" name="file_edit_form_target" height="0" width="0" style="display: none;"></iframe>
		<form action="/bitrix/admin/olympia_visual.php?lang=<?=LANGUAGE_ID ?>" method="post" name="editor_form" enctype="multipart/form-data" target="file_edit_form_target">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="field" value="<?=urlencode(json_encode($arValues['~FIELDS'])) ?>">
			<input type="submit" name="submitbtn" style="display: none;" />
			<input type="hidden" name="back_url" value="<?=$_REQUEST['back_url'] ?>"  />
			<div class="adm-detail-content">
				<div class="adm-detail-title">Редактирование области</div>
				<div class="adm-detail-content-item-block">
					<table class="adm-detail-content-table edit-table">
						<? foreach ($arValues['FIELDS'] as $code => $value): ?>
							<tr id="tr_<?=$code ?>_TITLE">
								<td class="adm-detail-content-cell-l"><span class="adm-required-field"><?=$value['name'] ?></span></td>
							</tr>
							<tr id="tr_<?=$code ?>_VALUE">
								<td class="adm-detail-content-cell-r">
									<?

										$prop = [
											'ID' => $code,
											'PROPERTY_TYPE' => 'S',
											'USER_TYPE' => '',
											'VALUE' => $fieldValues[$code],
											'~VALUE' => $fieldValues[$code],
											'DEFAULT_VALUE' => '',
											'WITH_DESCRIPTION' => 'N',
											'MULTIPLE' => (isset($value['multiple']) && $value['multiple'] === 'Y' ? 'Y' : 'N')
										];

										switch ($value['type'])
										{
											case 'INPUT':

												$prop['PROPERTY_TYPE'] = 'S';

												break;

											case 'HTML':

												$prop['PROPERTY_TYPE'] = 'S';
												$prop['USER_TYPE'] = 'HTML';
												$prop['MODE'] = 'FORM_FILL';

												break;

											case 'FILE':
											case 'SLIDER':
											case 'SLIDER_PREVIEW':

												$prop['PROPERTY_TYPE'] = 'F';
												$prop['WITH_DESCRIPTION'] = 'Y';

												break;

											case 'MAP':

												$prop['PROPERTY_TYPE'] = 'S';
												$prop['USER_TYPE'] = 'map_google';

												break;
										}

										_ShowPropertyField(
											'PROP['.$code.']',
											$prop,
											$prop["VALUE"],
											false,
											false,
											50000,
											"editor_form",
											false
										);
									?>
								</td>
							</tr>
						<? endforeach; ?>
					</table>
				</div>
			</div>
		</form>
	</div>
	<div class="bx-core-adm-dialog-buttons">
		<input type="button" class="adm-btn-save" id="btn_popup_save" name="btn_popup_save" value="Сохранить" onclick="BXFormSubmit();" title="Сохранить">
	</div>
</div>
<?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");