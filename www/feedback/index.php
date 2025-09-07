<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Задание 1");?>
<div style="max-width: 600px; margin: 50px auto;">
	<h1>Форма обратной связи</h1>
<?php
$APPLICATION->IncludeComponent(
    "bitrix:main.feedback",
    "bootstrap_v4",
    Array(
        "EMAIL_TO" => "workslavanto@gmail.com",
        "EVENT_MESSAGE_ID" => array("7"),
        "OK_TEXT" => "Спасибо, ваше сообщение принято.",
        "REQUIRED_FIELDS" => array("NAME","EMAIL"),
        "USE_CAPTCHA" => "Y"
    )
);?>
</div>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>