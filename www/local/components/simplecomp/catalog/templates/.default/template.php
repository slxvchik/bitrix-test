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
            <li><strong><?= $news['NAME']; ?></strong> - <?= $news['DATE']; ?> (<?= implode(", ", $news['SECTION_NAMES']); ?>)</li>
            <ul>
            <?php foreach ($news['PRODUCTS'] as $product): ?>

                <?php foreach($product['OFFERS'] as $offer): ?>

                    <li>
                        <?= $offer['NAME']; ?> - <?= $offer['PRICE']; ?> - <?= $offer['ARTNUMBER']; ?> - <?= $product['MATERIAL']; ?>
                    </li>

                <?php endforeach;?>

            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </ul>
</div>
