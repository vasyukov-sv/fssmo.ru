<?php

/**
 * @var CUser $USER
 * @var CMain $APPLICATION
 */

$aMenu = [];

$aMenu[] = [
	"parent_menu" => "global_menu_services",
	"section" => "reports",
	"sort" => 9991,
	"text" => "Список стрелков",
	"title" => "Список стрелков",
	"url" => "fssmo_shooters_list.php?lang=".LANGUAGE_ID,
	"icon" => "user_menu_icon",
	"page_icon" => "",
	"items_id" => "menu_reports",
	"more_url" => [],
	"items" => []
];

$aMenu[] = [
	"parent_menu" => "global_menu_services",
	"section" => "reports",
	"sort" => 9992,
	"text" => "Регистрации на соревнования",
	"title" => "Регистрации на соревнования",
	"url" => "fssmo_registrations.php?lang=".LANGUAGE_ID,
	"icon" => "user_menu_icon",
	"page_icon" => "",
	"items_id" => "menu_reports",
	"more_url" => [],
	"items" => []
];

return $aMenu;