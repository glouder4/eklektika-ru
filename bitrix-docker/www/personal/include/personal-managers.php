<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
} ?>
<?php

global $USER;

if (!$USER->IsAuthorized()) {
    return;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/personal_cabinet/PersonalManagersProvider.php';

$managers = PersonalManagersProvider::loadForUserId((int)$USER->GetID());
if ($managers === []) {
    return;
}

$telHref = static function (string $phone): string {
    $d = \preg_replace('/[^\d+]/', '', $phone);

    return $d !== '' ? $d : '';
};

$renderSocialIcon = static function (string $type): string {
    if ($type === 'TELEGRAM') {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="23" height="20" viewBox="0 0 23 20" fill="none" aria-hidden="true">'
            . '<path d="M22.3989 2.47742L19.0863 18.7283C18.8392 19.8728 18.2052 20.1306 17.289 19.6139L12.3201 15.7737L9.88764 18.2105C9.64166 18.4694 9.39458 18.7283 8.83046 18.7283L9.21857 13.3725L18.4872 4.54645C18.8742 4.13975 18.3812 3.99196 17.8881 4.32534L6.36407 11.9324L1.39411 10.3445C0.301948 9.97563 0.301948 9.19889 1.64119 8.68335L20.9536 0.816248C21.9047 0.520673 22.7159 1.0385 22.3989 2.47742Z" fill="#222222"></path>'
            . '</svg>';
    }
    if ($type === 'MAX') {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="23" height="20" viewBox="0 0 23 20" fill="none" aria-hidden="true">'
            . '<rect width="23" height="20" rx="4" fill="#222222"></rect>'
            . '<text x="11.5" y="14" text-anchor="middle" fill="#ffffff" font-size="9" font-family="Arial, sans-serif">MAX</text>'
            . '</svg>';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="23" height="20" viewBox="0 0 23 20" fill="none" aria-hidden="true">'
        . '<circle cx="11.5" cy="10" r="8" fill="#222222"></circle>'
        . '</svg>';
};

?>
        <div id="personal-manager--wrapper">
            <?php foreach ($managers as $manager) {
                $mid = (int)$manager['ID'];
                $clipId = 'pm-email-clip-' . $mid;
                $socialLinks = \is_array($manager['SOCIAL_LINKS'] ?? null) ? $manager['SOCIAL_LINKS'] : [];
                ?>
            <div class="manager-card-fields" data-manager-slot="<?= (int)$manager['SLOT'] ?>">
                <div class="manager-personal-info">
                    <div class="manager--avatar_field">
                        <?php if (($manager['PREVIEW_SRC'] ?? '') !== '') { ?>
                        <img src="<?= htmlspecialcharsbx($manager['PREVIEW_SRC']) ?>" width="60" height="60" alt="">
                        <?php } else { ?>
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="60" height="60" viewBox="0 0 256 256" xml:space="preserve">
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 45 88 c -11.049 0 -21.18 -2.003 -29.021 -8.634 C 6.212 71.105 0 58.764 0 45 C 0 20.187 20.187 0 45 0 c 24.813 0 45 20.187 45 45 c 0 13.765 -6.212 26.105 -15.979 34.366 C 66.181 85.998 56.049 88 45 88 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(214,214,214); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round"/>
                                <path d="M 45 60.71 c -11.479 0 -20.818 -9.339 -20.818 -20.817 c 0 -11.479 9.339 -20.818 20.818 -20.818 c 11.479 0 20.817 9.339 20.817 20.818 C 65.817 51.371 56.479 60.71 45 60.71 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(165,164,164); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round"/>
                                <path d="M 45 90 c -10.613 0 -20.922 -3.773 -29.028 -10.625 c -0.648 -0.548 -0.88 -1.444 -0.579 -2.237 C 20.034 64.919 31.933 56.71 45 56.71 s 24.966 8.209 29.607 20.428 c 0.301 0.793 0.069 1.689 -0.579 2.237 C 65.922 86.227 55.613 90 45 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(165,164,164); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round"/>
                            </g>
                        </svg>
                        <?php } ?>
                    </div>
                    <div class="manager--info">
                        <?php if (($manager['WORK_POSITION'] ?? '') !== '') { ?>
                        <div class="field post">
                            <span><?= htmlspecialcharsbx($manager['WORK_POSITION']) ?></span>
                        </div>
                        <?php } ?>
                        <div class="field name">
                            <span><?= htmlspecialcharsbx(($manager['NAME'] ?? '') !== '' ? $manager['NAME'] : 'Персональный менеджер') ?></span>
                        </div>
                    </div>
                </div>

                <div class="manager-action-links--wrapper">
                    <?php if (($manager['PHONE'] ?? '') !== '') {
                        $tel = $telHref((string)$manager['PHONE']);
                        ?>
                    <div class="phone-link link">
                        <a href="<?= $tel !== '' ? 'tel:' . htmlspecialcharsbx($tel) : '#' ?>">
                            <div class="icon"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16.5643 12.7424L14.3315 10.5095C13.534 9.71209 12.1784 10.0311 11.8594 11.0678C11.6202 11.7855 10.8227 12.1842 10.105 12.0247C8.51012 11.626 6.35702 9.5526 5.9583 7.87797C5.71906 7.16024 6.19753 6.36279 6.91523 6.12359C7.95191 5.80461 8.27089 4.44895 7.47344 3.65151L5.2406 1.41866C4.60264 0.860447 3.64571 0.860447 3.08749 1.41866L1.57235 2.93381C0.0572004 4.5287 1.73184 8.75516 5.47983 12.5032C9.22782 16.2511 13.4543 18.0056 15.0492 16.4106L16.5643 14.8955C17.1226 14.2575 17.1226 13.3006 16.5643 12.7424Z" fill="#0065FF"></path>
                                </svg>
                            </div>
                            <div class="data"><?= htmlspecialcharsbx($manager['PHONE']) ?></div>
                        </a>
                    </div>
                    <?php } ?>
                    <?php if (($manager['EMAIL'] ?? '') !== '') { ?>
                    <div class="email-link link">
                        <a href="mailto:<?= htmlspecialcharsbx($manager['EMAIL']) ?>">
                            <div class="icon"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#<?= htmlspecialcharsbx($clipId) ?>)">
                                        <path d="M11.9316 9.09224L17.9999 12.9285V5.09399L11.9316 9.09224Z" fill="#0065FF"></path>
                                        <path d="M0 5.09399V12.9285L6.06825 9.09224L0 5.09399Z" fill="#0065FF"></path>
                                        <path d="M16.8754 2.8125H1.12543C0.564055 2.8125 0.118555 3.231 0.0341797 3.76988L9.00043 9.67725L17.9667 3.76988C17.8823 3.231 17.4368 2.8125 16.8754 2.8125Z" fill="#0065FF"></path>
                                        <path d="M10.9014 9.77188L9.30951 10.8204C9.21501 10.8823 9.10813 10.9126 9.00013 10.9126C8.89213 10.9126 8.78526 10.8823 8.69076 10.8204L7.09888 9.77075L0.0361328 14.2381C0.122758 14.7725 0.566008 15.1876 1.12513 15.1876H16.8751C17.4343 15.1876 17.8775 14.7725 17.9641 14.2381L10.9014 9.77188Z" fill="#0065FF"></path>
                                    </g>
                                    <defs>
                                        <clipPath id="<?= htmlspecialcharsbx($clipId) ?>">
                                            <rect width="18" height="18" fill="white"></rect>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                            <div class="data"><?= htmlspecialcharsbx($manager['EMAIL']) ?></div>
                        </a>
                    </div>
                    <?php } ?>
                </div>

                <?php if ($socialLinks !== []) { ?>
                <div class="our-social_links">
                    <div class="title">
                        <span>Мы в сети</span>
                    </div>
                    <div class="links">
                        <?php foreach ($socialLinks as $social) {
                            $url = (string)($social['URL'] ?? '');
                            $type = (string)($social['TYPE'] ?? '');
                            if ($url === '') {
                                continue;
                            }
                            ?>
                        <a href="<?= htmlspecialcharsbx($url) ?>" target="_blank" rel="nofollow noopener" class="link" title="<?= htmlspecialcharsbx($type) ?>">
                            <?= $renderSocialIcon($type) ?>
                        </a>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
