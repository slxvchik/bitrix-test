<?php

use Bitrix\Catalog\PriceTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SimpleCompCatalog extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['PRODUCTS_IBLOCK_ID'] = (int)$arParams['PRODUCTS_IBLOCK_ID'];
        $arParams['NEWS_IBLOCK_ID'] = (int)$arParams['NEWS_IBLOCK_ID'];
        $arParams['UF_PROPERTY_CODE'] = trim($arParams['UF_PROPERTY_CODE']);
        $arParams['CACHE_TIME'] = (int)$arParams['CACHE_TIME'];

        return $arParams;
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    public function executeComponent(): void
    {

        if (!$this->validateParams()) {
            ShowError('Неверные входные параметры');
            return;
        }


        if ($this->StartResultCache()) {
            $this->arResult = $this->getData();

            $this->setPageTitle();

            $this->includeComponentTemplate();

            $this->EndResultCache();
        }
    }

    private function validateParams(): bool
    {
        return $this->arParams['PRODUCTS_IBLOCK_ID'] > 0
            && $this->arParams['NEWS_IBLOCK_ID'] > 0
            && !empty($this->arParams['UF_PROPERTY_CODE']);
    }

    private function setPageTitle(): void
    {
        global $APPLICATION;
        $APPLICATION->SetTitle(
            "В каталоге товаров представлено товаров: " . $this->arResult['PRODUCTS_COUNT']
        );
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getData(): array
    {
        $result = [
            'NEWS' => [],
            'PRODUCTS_COUNT' => 0
        ];

        $news = $this->getNews();
        if (empty($news)) return $result;

        $sectionsMap = $this->getSectionsMap();
        if (empty($sectionsMap)) return $result;

        $allSectionIds = [];
        foreach ($sectionsMap as $sectionIds) {
            $allSectionIds = array_merge($allSectionIds, $sectionIds['CATALOG_IDS']);
        }
        $allSectionIds = array_unique($allSectionIds);

        $products = $this->getProducts($allSectionIds);

        if (empty($products['COUNT'])) return $result;

        $result['PRODUCTS_COUNT'] = $products['COUNT'];

        foreach ($news as $newsId => $newsItem) {
            if (!isset($sectionsMap[$newsId]['CATALOG_IDS'])) continue;

            $item = [
                'ID' => $newsItem['ID'],
                'NAME' => $newsItem['NAME'],
                'DATE' => $newsItem['ACTIVE_FROM'],
                'PRODUCTS' => [],
                'SECTIONS' => $sectionsMap[$newsId]['CATALOG_NAMES']
            ];

            foreach ($sectionsMap[$newsId]['CATALOG_IDS'] as $sectionId) {
                if (isset($products['SECTIONS'][$sectionId])) {
                    array_push($item['PRODUCTS'], ...$products['SECTIONS'][$sectionId]);
                }
            }

            $result['NEWS'][] = $item;
        }

        return $result;
    }

    private function getNews(): array
    {
        $news = [];
        $filter = ['IBLOCK_ID' => $this->arParams['NEWS_IBLOCK_ID'], 'ACTIVE' => 'Y'];
        $select = ['ID', 'NAME', 'ACTIVE_FROM'];

        $rsNews = CIBlockElement::GetList(array(), $filter, false, false, $select);

        while ($item = $rsNews->GetNext()) {
            $news[$item['ID']] = $item;
        }

        return $news;
    }

    // Каталоги товара в новостях
    private function getSectionsMap(): array
    {
        $map = [];
        $filter = [
            'IBLOCK_ID' => $this->arParams['PRODUCTS_IBLOCK_ID'],
            'ACTIVE' => 'Y',
            '!'.$this->arParams['UF_PROPERTY_CODE'] => false
        ];

        $select = ['ID', 'NAME', $this->arParams['UF_PROPERTY_CODE']];

        $rsSections = CIBlockSection::GetList(array(), $filter, false, $select);
        while ($section = $rsSections->GetNext()) {
            $newsLinks = is_array($section[$this->arParams['UF_PROPERTY_CODE']])
                ? $section[$this->arParams['UF_PROPERTY_CODE']]
                : [$section[$this->arParams['UF_PROPERTY_CODE']]];

            foreach ($newsLinks as $newsId) {
                $map[$newsId]['CATALOG_IDS'][] = $section['ID'];
                $map[$newsId]['CATALOG_NAMES'][] = $section['NAME'];
            }
        }

        return $map;
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getProducts($sectionIds): array
    {
        if (empty($sectionIds)) return [];

        $products = [
            'SECTIONS' => [],
            'COUNT' => 0
        ];

        $filter = [
            'IBLOCK_ID' => $this->arParams['PRODUCTS_IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'SECTION_GLOBAL_ACTIVE' => 'Y',
            'SECTION_ID' => $sectionIds,
        ];

        $select = [
            'ID', 'NAME', 'IBLOCK_SECTION_ID',
            'PROPERTY_MATERIAL', 'PROPERTY_ARTNUMBER'
        ];

        $productIds = [];
        $productsData = [];

        $rsProducts = CIBlockElement::GetList([], $filter, false, false, $select);
        while ($product = $rsProducts->Fetch()) {
            $productIds[] = $product['ID'];
            $productsData[$product['ID']] = $product;
        }

        $prices = $this->getBasePricesBatch($productIds);

        foreach ($productsData as $productId => $product) {
            $sectionId = $product['IBLOCK_SECTION_ID'];
            $products['SECTIONS'][$sectionId][] = [
                'NAME' => $product['NAME'],
                'PRICE' => $prices[$productId],
                'ARTICLE' => $product['PROPERTY_ARTNUMBER_VALUE'],
                'MATERIAL' => $product['PROPERTY_MATERIAL_VALUE']
            ];
            $products['COUNT']++;
        }

        return $products;
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getBasePricesBatch(array $productIds): array
    {
        if (empty($productIds) || !CModule::IncludeModule("catalog")) {
            return [];
        }

        $priceIterator = PriceTable::getList([
            'filter' => [
                'PRODUCT_ID' => $productIds,
                'CATALOG_GROUP_ID' => 1
            ],
            'select' => ['PRODUCT_ID', 'PRICE']
        ]);

        $prices = [];
        while ($price = $priceIterator->fetch()) {
            $prices[$price['PRODUCT_ID']] = $price['PRICE'];
        }

        return $prices;
    }
}