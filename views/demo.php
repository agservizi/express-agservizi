<?php
declare(strict_types=1);

$appName = $GLOBALS['config']['app']['name'] ?? 'Coresuite Express';
$pageTitle = $pageTitle ?? ($appName . ' - Demo');
$loginUrl = 'index.php?page=login';
$landingUrl = 'index.php?page=landing';
$currentPage = 'demo';
$baseUrl = (string) ($GLOBALS['config']['app']['base_url'] ?? 'https://express.agenziaplinio.it');
$metaDescription = 'Demo del gestionale per negozi di telefonia: flussi vendita, magazzino SIM e report in un unico cruscotto.';
$canonical = $baseUrl . '/index.php?page=demo';
$ogImage = $baseUrl . '/assets/img/logo-collapsed.svg';
$demoSlides = [
    'Screenshot 2026-01-31 alle 18.27.35.png',
    'Screenshot 2026-01-31 alle 18.27.42.png',
    'Screenshot 2026-01-31 alle 18.27.56.png',
    'Screenshot 2026-01-31 alle 18.28.03.png',
];
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta name="theme-color" content="#2563eb">
    <link rel="preload" href="assets/css/styles.css" as="style">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="landing-body">
    <div class="landing-topbar">
        <div class="landing-topbar__inner">
            <div class="landing-brand">
                <span class="landing-brand__logo" aria-hidden="true">
                    <img src="assets/img/logo-collapsed.svg" alt="">
                </span>
                <span class="landing-brand__name"><?= htmlspecialchars($appName) ?></span>
            </div>
            <nav class="landing-nav" aria-label="Navigazione principale">
                <a class="landing-nav__link <?= $currentPage === 'landing' ? 'landing-nav__link--active' : '' ?>" href="<?= htmlspecialchars($landingUrl) ?>">Home</a>
                <a class="landing-nav__link <?= $currentPage === 'demo' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=demo">Demo</a>
                <a class="landing-nav__link <?= $currentPage === 'funzionalita' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=funzionalita">Funzionalità</a>
                <a class="landing-nav__link <?= $currentPage === 'vantaggi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=vantaggi">Vantaggi</a>
                <a class="landing-nav__link <?= $currentPage === 'piani' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=piani">Piani</a>
                <a class="landing-nav__link <?= $currentPage === 'prezzi' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=prezzi">Prezzi</a>
                <a class="landing-nav__link <?= $currentPage === 'faq' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=faq">FAQ</a>
                <a class="landing-nav__link <?= $currentPage === 'contatto' ? 'landing-nav__link--active' : '' ?>" href="index.php?page=contatto">Contatto</a>
            </nav>
            <a class="landing-btn landing-btn--ghost" href="<?= htmlspecialchars($loginUrl) ?>">Accedi</a>
        </div>
    </div>

    <header class="landing-hero landing-hero--compact">
        <div class="landing-hero__content">
            <div class="landing-hero__text">
                <p class="landing-kicker">Esperienza guidata in pochi minuti</p>
                <h1>Demo del gestionale</h1>
                <p class="landing-subtitle">
                    Scopri il flusso di vendita, il magazzino SIM e i report KPI in un’unica piattaforma pensata per i punti vendita.
                </p>
                <div class="landing-hero__actions">
                    <a class="landing-btn landing-btn--secondary" href="index.php?page=contatto">Richiedi demo personalizzata</a>
                    <a class="landing-btn landing-btn--primary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
                </div>
            </div>
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-section landing-demo" id="demo">
            <div class="landing-section__header">
                <h2>Anteprima interattiva</h2>
                <p>Schermate reali per valutare operatività, dashboard e flussi SIM.</p>
            </div>
            <div class="landing-demo__grid">
                <div>
                    <ul class="landing-demo__list">
                        <li>Flusso vendita rapido con stampa scontrino.</li>
                        <li>Gestione SIM e scorte con alert automatici.</li>
                        <li>Dashboard KPI per ogni punto vendita.</li>
                    </ul>
                    <a class="landing-btn landing-btn--primary" href="index.php?page=contatto">Richiedi demo personalizzata</a>
                </div>
                <div class="landing-demo__frame" aria-label="Anteprima demo piattaforma">
                    <div class="landing-demo__slider" data-demo-slider>
                        <div class="landing-demo__slides">
                            <?php foreach ($demoSlides as $index => $slide): ?>
                                <figure class="landing-demo__slide" data-demo-slide <?= $index === 0 ? 'data-active="true"' : '' ?>>
                                    <img src="assets/img/<?= rawurlencode($slide) ?>" alt="Anteprima gestionale <?= $index + 1 ?>" loading="lazy">
                                </figure>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="landing-demo__nav landing-demo__nav--prev" data-demo-prev aria-label="Precedente">‹</button>
                        <button type="button" class="landing-demo__nav landing-demo__nav--next" data-demo-next aria-label="Successivo">›</button>
                        <div class="landing-demo__dots" role="tablist" aria-label="Selezione slide">
                            <?php foreach ($demoSlides as $index => $_): ?>
                                <button type="button" class="landing-demo__dot" data-demo-dot="<?= $index ?>" aria-label="Slide <?= $index + 1 ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section--accent">
            <div class="landing-section__header">
                <h2>Vuoi una demo guidata?</h2>
                <p>Ti inviamo credenziali e supporto dedicato per configurare il tuo primo store.</p>
            </div>
            <div class="landing-hero__actions">
                <a class="landing-btn landing-btn--primary" href="index.php?page=contatto">Richiedi informazioni</a>
                <a class="landing-btn landing-btn--secondary" href="<?= htmlspecialchars($loginUrl) ?>">Accedi alla demo</a>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div>
            <strong><?= htmlspecialchars($appName) ?></strong>
            <span>· Gestionale multi-tenant per telecomunicazioni e retail.</span>
        </div>
        <span>Sviluppato e distribuito da AG SERVIZI P.Iva 08442881218</span>
    </footer>

    <script>
    (function () {
        const slider = document.querySelector('[data-demo-slider]');
        if (!slider) {
            return;
        }
        const slides = Array.from(slider.querySelectorAll('[data-demo-slide]'));
        const dots = Array.from(slider.querySelectorAll('[data-demo-dot]'));
        const prevBtn = slider.querySelector('[data-demo-prev]');
        const nextBtn = slider.querySelector('[data-demo-next]');
        if (slides.length === 0) {
            return;
        }
        let index = slides.findIndex(slide => slide.dataset.active === 'true');
        if (index < 0) {
            index = 0;
            slides[0].dataset.active = 'true';
        }

        let timerId = 0;

        const update = (nextIndex) => {
            slides.forEach((slide, idx) => {
                if (idx === nextIndex) {
                    slide.dataset.active = 'true';
                } else {
                    delete slide.dataset.active;
                }
            });
            dots.forEach((dot, idx) => {
                dot.setAttribute('aria-pressed', idx === nextIndex ? 'true' : 'false');
            });
            index = nextIndex;
        };

        const go = (direction) => {
            const nextIndex = (index + direction + slides.length) % slides.length;
            update(nextIndex);
        };

        const startAutoplay = () => {
            if (timerId) {
                window.clearInterval(timerId);
            }
            timerId = window.setInterval(() => go(1), 4500);
        };

        const stopAutoplay = () => {
            if (timerId) {
                window.clearInterval(timerId);
                timerId = 0;
            }
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                go(-1);
                startAutoplay();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                go(1);
                startAutoplay();
            });
        }
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                update(idx);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);
        startAutoplay();
    })();
    </script>
</body>
</html>
