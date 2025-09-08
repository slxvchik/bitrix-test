<?php
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

    public function executeComponent()
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

    private function getData()
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

    private function getNews()
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
    private function getSectionsMap()
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

    private function getProducts($sectionIds)
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
            'SECTION_ID' => $sectionIds
        ];

        $select = [
            'ID', 'NAME', 'IBLOCK_SECTION_ID',
            'PROPERTY_MATERIAL', 'PROPERTY_ARTNUMBER', 'PROPERTY_PRICE'
        ];

        $rsProducts = CIBlockElement::GetList([], $filter, false, false, $select);
        while ($product = $rsProducts->Fetch()) {
            $sectionId = $product['IBLOCK_SECTION_ID'];
            $products['SECTIONS'][$sectionId][] = [
                'NAME' => $product['NAME'],
                'PRICE' => $product['PROPERTY_PRICE_VALUE'],
                'ARTICLE' => $product['PROPERTY_ARTNUMBER_VALUE'],
                'MATERIAL' => $product['PROPERTY_MATERIAL_VALUE']
            ];
            $products['COUNT']++;
        }

        return $products;
    }
}