<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?><?$APPLICATION->IncludeComponent(
	"simplecomp:catalog", 
	".default", 
	array(
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "360000",
		"CACHE_TYPE" => "N",
		"NEWS_IBLOCK_ID" => "1",
		"PRODUCTS_IBLOCK_ID" => "2",
		"UF_PROPERTY_CODE" => "UF_NEWS_LINK",
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>