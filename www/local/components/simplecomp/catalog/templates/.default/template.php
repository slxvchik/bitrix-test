<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>
<div class="simplecomp-catalog">
    <h1>Элементов: <?= $arResult['PRODUCTS_COUNT']; ?></h1>
    <h2>Каталог: </h2>
    <?php if (empty($arResult['NEWS'])): ?>
    <p>Нет данных для отображения</p>
    <?php return; endif; ?>
    <ul>
        <?php foreach ($arResult['NEWS'] as $news): ?>
            <li><span><?= $news['NAME']; ?></span> - <?= $news['DATE']; ?> (<?= implode(", ", $news['SECTIONS']); ?>)</li>
            <ul>
            <?php foreach ($news['PRODUCTS'] as $product): ?>
                <li>
                <?= $product['NAME']; ?> - <?= $product['PRICE']; ?> - <?= $product['MATERIAL']; ?> - <?= $product['ARTICLE']; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </ul>
</div>
