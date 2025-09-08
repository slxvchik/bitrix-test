<?php

spl_autoload_register(function ($className) {
    $classFile = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/' . str_replace('\\', '/', $className) . '.php';

    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

AddEventHandler('main', 'OnBeforeEventAdd',
    array('Event\FeedbackForm\FeedbackEventHandler', 'onBeforeEventAddHandler')
);