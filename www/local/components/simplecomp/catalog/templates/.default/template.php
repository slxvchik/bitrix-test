<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>
<div class="simplecomp-catalog">
    <h1>Каталог: </h1>
    <?php if (empty($arResult['NEWS'])): ?>
    <p>Нет данных для отображения</p>
    <?php return; endif; ?>
    <ul>
        <?php foreach ($arResult['NEWS'] as $news): ?>
            <li><strong><?= $news['NAME']; ?></strong> - <?= $news['DATE']; ?> (<?= implode(", ", $news['SECTIONS']); ?>)</li>
            <ul>
            <?php foreach ($news['PRODUCTS'] as $product): ?>
                <li>
                <?= $product['NAME']; ?> - <?= $product['PRICE']; ?> - <?= $product['ARTICLE']; ?> - <?= $product['MATERIAL']; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </ul>
</div>
