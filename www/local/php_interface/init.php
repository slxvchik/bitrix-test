<?php

AddEventHandler('main', 'OnBeforeEventAdd', function(&$event, &$lid, &$arFields) {

    if ($event === 'FEEDBACK_FORM') {

        global $USER;
        $username = $arFields['AUTHOR'] ?? '';

        if ($USER->IsAuthorized()) {
            $userId = $USER->GetID();
            $userLogin = $USER->GetLogin();
            $userFirstName = $USER->GetFirstName();

            $arFields['AUTHOR'] = "Пользователь авторизован: {$userId} ({$userLogin}) {$userFirstName}, данные из формы: {$username}";
        } else {
            $arFields['AUTHOR'] = "Пользователь не авторизован, данные из формы: {$username}";
        }

        CEventLog::Add(array(
            'SEVERITY' => 'INFO',
            'AUDIT_TYPE_ID' => 'FEEDBACK_FORM_MAIL_AUTHOR_MODIFY',
            'MODULE_ID' => 'main',
            'DESCRIPTION' => "Замена данных в макросе #AUTHOR при отправке формы обратной связи. Поле AUTHOR: " . $arFields['AUTHOR'],
        ));

    }
});