<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SimpleCompCatalog extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['PRODUCTS_IBLOCK_ID'] = (int)$arParams['PRODUCTS_IBLOCK_ID'];
        $arParams['NEWS_IBLOCK_ID'] = (int)$arParams['NEWS_IBLOCK_ID'];
        $arParams['OFFERS_IBLOCK_ID'] = (int)$arParams['OFFERS_IBLOCK_ID'];
        $arParams['UF_PROPERTY_CODE'] = trim($arParams['UF_PROPERTY_CODE']);
        $arParams['CACHE_TIME'] = (int)$arParams['CACHE_TIME'];

        return $arParams;
    }

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

    private function getOffersForProducts($productIds): array
    {

        $rsOffers = CIBlockElement::GetList(
            array(),
            array(
                'IBLOCK_ID' => $this->arParams['OFFERS_IBLOCK_ID'],
                'PROPERTY_CML2_LINK' => $productIds,
            ),
            false,
            false,
            array(
                'ID',
                'NAME',
                'CATALOG_PRICE_1',
                'CATALOG_QUANTITY',
                'CATALOG_AVAILABLE',
                'PROPERTY_ARTNUMBER',
                'PROPERTY_CML2_LINK',
            )
        );

        $offers = array();

        while ($offer = $rsOffers->GetNext()) {
            $productId = $offer['PROPERTY_CML2_LINK_VALUE'];
            $offers[$productId][] = array(
                'ID' => $offer['ID'],
                'NAME' => $offer['NAME'],
                'ARTNUMBER' => $offer['PROPERTY_ARTNUMBER_VALUE'],
                'PRICE' => $offer['CATALOG_PRICE_1']
            );
        }

        return $offers;
    }

    private function validateParams(): bool
    {
        return $this->arParams['PRODUCTS_IBLOCK_ID'] > 0
            && $this->arParams['NEWS_IBLOCK_ID'] > 0
            && $this->arParams['OFFERS_IBLOCK_ID'] > 0
            && !empty($this->arParams['UF_PROPERTY_CODE']);
    }

    private function setPageTitle(): void
    {
        global $APPLICATION;
        $APPLICATION->SetTitle(
            "В каталоге товаров представлено товаров: " . $this->arResult['PRODUCTS_COUNT']
        );
    }

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

        $products = $this->getProductsWithOffers($allSectionIds);

        if (empty($products['COUNT'])) return $result;

        $result['PRODUCTS_COUNT'] = $products['COUNT'];

        foreach ($news as $newsId => $newsItem) {

            if (!isset($sectionsMap[$newsId]['CATALOG_IDS'])) continue;

            $item = array(
                'ID' => $newsItem['ID'],
                'NAME' => $newsItem['NAME'],
                'DATE' => $newsItem['ACTIVE_FROM'],
                'PRODUCTS' => array(),
                'CATALOG_NAMES' => $sectionsMap[$newsId]['CATALOG_NAMES']
            );

            foreach ($sectionsMap[$newsId]['CATALOG_IDS'] as $catalogId) {
                if (isset($products['SECTIONS'][$catalogId])) {
                    array_push($item['PRODUCTS'], ...$products['SECTIONS'][$catalogId]);
                }
            }

            $result['NEWS'][] = $item;
        }

        return $result;
    }

    private function getNews(): array
    {
        $news = array();
        $filter = array(
            'IBLOCK_ID' => $this->arParams['NEWS_IBLOCK_ID'],
            'ACTIVE' => 'Y'
        );
        $select = array('ID', 'NAME', 'ACTIVE_FROM');

        $rsNews = CIBlockElement::GetList(array(), $filter, false, false, $select);

        while ($item = $rsNews->GetNext()) {
            $news[$item['ID']] = $item;
        }

        return $news;
    }

    // Каталоги товара привязанных к новостям
    private function getSectionsMap(): array
    {
        $map = array();
        $filter = array(
            'IBLOCK_ID' => $this->arParams['PRODUCTS_IBLOCK_ID'],
            'ACTIVE' => 'Y',
            '!'.$this->arParams['UF_PROPERTY_CODE'] => false
        );

        $select = array('ID', 'NAME', $this->arParams['UF_PROPERTY_CODE']);

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

    private function getProductsWithOffers($sectionIds): array
    {
        if (empty($sectionIds)) return [];

        $products = array(
            'SECTIONS' => array(),
            'COUNT' => 0
        );

        $filter = array(
            'IBLOCK_ID' => $this->arParams['PRODUCTS_IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'SECTION_GLOBAL_ACTIVE' => 'Y',
            'SECTION_ID' => $sectionIds
        );

        $select = array(
            'ID', 'NAME', 'IBLOCK_SECTION_ID',
            'PROPERTY_MATERIAL', 'PROPERTY_ARTNUMBER'
        );

        $productIds = array();
        $productsData = array();

        $rsProducts = CIBlockElement::GetList(array(), $filter, false, false, $select);
        while ($product = $rsProducts->GetNext()) {
            $sectionId = $product['IBLOCK_SECTION_ID'];
            $productId = $product['ID'];

            $productIds[] = $productId;
            $productsData[$productId] = array(
                'SECTION_ID' => $sectionId,
                'DATA' => array(
                    'ID' => $product['ID'],
                    'NAME' => $product['NAME'],
                    'ARTNUMBER' => $product['PROPERTY_ARTNUMBER_VALUE'],
                    'MATERIAL' => $product['PROPERTY_MATERIAL_VALUE'],
                    'OFFERS' => array()
                )
            );
            $products['COUNT']++;
        }

        if (!empty($productIds)) {
            $offers = $this->getOffersForProducts($productIds);

            foreach ($offers as $productId => $productOffers) {
                if (isset($productsData[$productId])) {
                    $productsData[$productId]['DATA']['OFFERS'] = $productOffers;
                }
            }
        }

        foreach ($productsData as $productId => $productInfo) {
            $sectionId = $productInfo['SECTION_ID'];
            $products['SECTIONS'][$sectionId][] = $productInfo['DATA'];
        }

        return $products;
    }
}