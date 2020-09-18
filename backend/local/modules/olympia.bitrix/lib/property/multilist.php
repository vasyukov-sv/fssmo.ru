<?

/**
 * @author Olympia.Digital
 * @author Alexey Bobkov <ab@olympia.digital>
 * @copyright 2017 Olympia.Digital
 */

namespace Olympia\Bitrix\Property;

class MultiList
{
	public static function GetUserTypeDescription ()
	{
		return array
		(
			'PROPERTY_TYPE' 			=> 'E',
			'USER_TYPE' 				=> 'MultiList',
			'DESCRIPTION' 				=> 'Расширенная привязка к элементам',
			'GetPropertyFieldHtml' 		=> array('Olympia\\Bitrix\\Property\\MultiList', 'GetPropertyFieldHtml'),
			'GetPropertyFieldHtmlMulty' => array('Olympia\\Bitrix\\Property\\MultiList', 'GetPropertyFieldHtmlMulty'),
			'PrepareSettings' 			=> array('Olympia\\Bitrix\\Property\\MultiList', 'PrepareSettings'),
			'GetSettingsHTML' 			=> array('Olympia\\Bitrix\\Property\\MultiList', 'GetSettingsHTML'),
		);
	}

	public static function PrepareSettings ($arProperty)
	{
		return [];
	}

	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$settings = self::PrepareSettings($arProperty);

		$arPropertyFields = [
			"HIDE" => ["ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"],
		];

		return '';
	}

	public static function GetPropertyFieldHtml ($arPropertyOriginal, $values, $strHTMLControlName)
	{
		$settings = self::PrepareSettings($arPropertyOriginal);

		global $ID, $by, $order, $FIELDS_del, $f_ID, $f_LOCKED_USER_NAME, $f_USER_NAME, $f_MODIFIED_BY, $f_CREATED_BY, $f_WF_LOCKED_BY;

		$intSubIBlockID = $arPropertyOriginal['LINK_IBLOCK_ID'];
		$strSubIBlockType = '';
		$intSubPropValue = $arPropertyOriginal['ID'];
		$strSubTMP_ID = $ID;

		$bBadBlock = true;

		if (0 < $intSubIBlockID)
		{
			$arSubIBlock = \CIBlock::GetArrayByID($intSubIBlockID);

			if ($arSubIBlock)
			{
				$strSubIBlockType = $arSubIBlock['IBLOCK_TYPE_ID'];

				$bBadBlock = !\CIBlockRights::UserHasRightTo($intSubIBlockID, $intSubIBlockID, "iblock_admin_display");;
			}
		}

		if ($bBadBlock)
			return '';
		else
		{
			define('B_ADMIN_SUBELEMENTS',1);
			define('B_ADMIN_SUBELEMENTS_LIST',false);

			ob_start();

			$strSubElementAjaxPath = '/bitrix/admin/olympia_subelement_admin.php?WF=Y&IBLOCK_ID='.$intSubIBlockID.'&type='.urlencode($strSubIBlockType).'&lang='.LANGUAGE_ID.'&find_section_section=0&PRODUCT_ID='.intval($intSubPropValue).'&TMP_ID='.urlencode($strSubTMP_ID);

			require(dirname(__DIR__).'/../admin/template/subelement_list.php');

			$html = ob_get_contents();
			ob_end_clean();

			$html = strtr($html, [
				'ReloadSubList' => 'Reload_SubList'.$intSubPropValue,
				'ReloadOffers' 	=> 'Reload_Offers'.$intSubPropValue
			]);

			return $html;
		}
	}

	public static function GetPropertyFieldHtmlMulty ($arProperty, $values, $strHTMLControlName)
	{
		return self::GetPropertyFieldHtml ($arProperty, $values, $strHTMLControlName);
	}
}