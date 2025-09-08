<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    'PARAMETERS' => array(
        'PRODUCTS_IBLOCK_ID' => array(
            'NAME' => GetMessage('SIMPLE_COMP_CATALOG_IBLOCK_PRODUCTS_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ),
        'NEWS_IBLOCK_ID' => array(
            'NAME' => GetMessage('SIMPLE_COMP_CATALOG_IBLOCK_NEWS_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ),
        'OFFERS_IBLOCK_ID' => array(
            'NAME' => GetMessage('SIMPLE_COMP_CATALOG_IBLOCK_OFFERS_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ),
        'UF_PROPERTY_CODE' => array(
            'NAME' => GetMessage('SIMPLE_COMP_CATALOG_UF_PROPERTY_CODE'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'UF_NEWS_LINK',
        ),
        'CACHE_TIME' => array(
            'DEFAULT' => 36000000
        ),
    ),
];