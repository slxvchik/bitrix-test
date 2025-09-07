<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME' => GetMessage('SIMPLE_COMP_CATALOG_NAME'),
    'DESCRIPTION' => GetMessage('SIMPLE_COMP_CATALOG_DESC'),
    'PATH' => [
        'ID' => 'simplecomp',
        'name' => GetMessage('SIMPLE_COMP_CATALOG_PATH_NAME')
    ],
    'CACHE_PATH' => 'Y'
];