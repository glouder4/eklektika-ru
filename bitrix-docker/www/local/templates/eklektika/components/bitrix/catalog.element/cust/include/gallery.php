<div class="product-gallery gallery-fix">
    <div class="swiper-container gallery-top swiper-container-horizontal">
        <div class="swiper-wrapper">
            <?php foreach ($allPhotos as $index => $photo): ?>
                <a href="<?= htmlspecialcharsbx($photo['src']) ?>" 
                    class="swiper-slide fancybox-gallery <?= $index === 0 ? 'swiper-slide-active' : '' ?>" 
                    data-fancybox="gallery" 
                    title="<?= htmlspecialcharsbx($photo['alt']) ?>">
                    <img src="<?= htmlspecialcharsbx($photo['src']) ?>" 
                            alt="<?= htmlspecialcharsbx($photo['alt']) ?>">
                </a>
            <?php endforeach; ?>
        </div>
        <!-- Стрелки навигации -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
    
    <!-- Миниатюры -->
    <div class="swiper-container gallery-thumbs swiper-container-horizontal">
        <div class="swiper-wrapper">
            <?php foreach ($allPhotos as $index => $photo): ?>
                <div class="swiper-slide <?= $index === 0 ? 'swiper-slide-active' : '' ?>">
                    <img src="<?= htmlspecialcharsbx($photo['src']) ?>" 
                            alt="<?= htmlspecialcharsbx($photo['alt']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- end thumbs -->
</div>
<!-- end gallery -->