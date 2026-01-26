<?php foreach ($offersData as $offerId => $offer): ?>
    <li class="<?= $offer['isActive'] ? 'active' : '' ?>">
        <a href="<?= $offer['link'] ?>">
            <img src="<?= htmlspecialcharsbx($offer['imgSrc']) ?>"
                title="<?= htmlspecialcharsbx($offer['title']) ?>"
                alt="<?= htmlspecialcharsbx(str_replace([' ', ';'], ['-', '||color-'], mb_strtolower($offer['title'])) . '-f') ?>">
        </a>
    </li>
<?php endforeach; ?>
