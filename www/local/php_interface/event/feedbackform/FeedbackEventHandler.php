<?php

namespace Event\FeedbackForm;

use Bitrix\Main\Localization\Loc;
use CEventLog;

class FeedbackEventHandler
{

    public static function onBeforeEventAddHandler(&$event, &$lid, &$arFields)
    {
        if ($event === 'FEEDBACK_FORM') {
            self::processFeedbackForm($arFields);
        }
    }

    private static function processFeedbackForm(array &$arFields): void
    {
        global $USER;

        $username = $arFields['AUTHOR'] ?? '';
        $authorInfo = '';

        if ($USER->IsAuthorized()) {
            $userId = $USER->GetID();
            $userLogin = $USER->GetLogin();
            $userFirstName = $USER->GetFirstName();

            $authorInfo = Loc::getMessage('FEEDBACK_AUTHORIZED_USER', array(
                '#USER_ID#' => $userId,
                '#USER_LOGIN#' => $userLogin,
                '#USER_FIRST_NAME#' => $userFirstName,
                '#FORM_USERNAME#' => $username
            ));

        } else {
            $authorInfo = Loc::getMessage('FEEDBACK_UNAUTHORIZED_USER', array(
                '#FORM_USERNAME#' => $username
            ));
        }

        $arFields['AUTHOR'] = $authorInfo;

        self::logFeedbackEvent($authorInfo);
    }

    private static function logFeedbackEvent(string $authorInfo): void
    {
        CEventLog::Add([
            'SEVERITY' => 'INFO',
            'AUDIT_TYPE_ID' => 'FEEDBACK_FORM_MAIL_AUTHOR_MODIFY',
            'MODULE_ID' => 'main',
            'DESCRIPTION' => Loc::getMessage('FEEDBACK_LOG_DESCRIPTION', [
                '#AUTHOR_INFO#' => $authorInfo
            ]),
        ]);
    }
}