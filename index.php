<?php
require_once __DIR__ . '/includes/config.php';
$schoolName = srms_school_name($dbh);
$notices = [];
try {
    $noticeQuery = $dbh->prepare('SELECT id, noticeTitle, noticeDetails, postingDate FROM tblnotice WHERE Published = 1 ORDER BY postingDate DESC LIMIT 6');
    $noticeQuery->execute();
    $notices = $noticeQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Keep the public landing page available even if notices are temporarily unavailable.
    $notices = [];
}
function landing_excerpt($value, $length = 145) {
    $text = trim(preg_replace('/\\s+/', ' ', strip_tags((string)$value)));
    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) return mb_substr($text, 0, $length, 'UTF-8') . '…';
    if (strlen($text) > $length) return substr($text, 0, $length) . '…';
    return $text;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#173d70">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="description" content="Access published examination results, school announcements and secure staff portals from the school result portal.">
    <title><?php echo htmlentities($schoolName, ENT_QUOTES, 'UTF-8'); ?> | Student Portal</title>
    <link rel="icon" type="image/png" href="apple-touch-icon.png">
    <link rel="preload" as="image" href="images/srms-students-hero.jpg" fetchpriority="high">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/srms-landing.css">
<link rel="stylesheet" href="css/srms-responsive.css" media="screen">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <nav class="site-nav container" aria-label="Main navigation">
        <a class="brand" href="index.php" aria-label="SRMS home">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M4 11.5 16 5l12 6.5L16 18 4 11.5Z"/><path d="M8 14v7c4.8 4.2 11.2 4.2 16 0v-7M28 12v9"/></svg></span>
            <span class="brand-copy"><strong><?php echo htmlentities($schoolName, ENT_QUOTES, 'UTF-8'); ?></strong><small>STUDENT &amp; SCHOOL PORTAL</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation"><span></span><span></span><span></span></button>
        <div class="nav-links" id="primary-nav">
            <a class="nav-link active" href="index.php">Home</a>
            <a class="nav-link" href="#announcements">Announcements</a>
            <a class="nav-link" href="#portals">Portals</a>
            <a class="nav-login" href="login.php">Staff login <span aria-hidden="true">↗</span></a>
        </div>
    </nav>
</header>
<main id="main-content">
    <section class="hero-section">
        <div class="hero-shape hero-shape-one"></div><div class="hero-shape hero-shape-two"></div>
        <div class="container hero-grid">
            <div class="hero-copy">
                <div class="eyebrow"><span class="eyebrow-dot"></span> YOUR SCHOOL, CONNECTED</div>
                <h1>Your academic journey, <span>all in one place.</span></h1>
                <p class="hero-lead">A simpler way to access published examination results, keep up with school announcements and reach the right school services.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="find-result.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg> Check results <span aria-hidden="true">→</span></a>
                    <a class="button button-outline" href="#portals">Explore portals <span aria-hidden="true">↓</span></a>
                </div>
                <div class="hero-trust"><span class="trust-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 19 6v5c0 5-3.2 8.2-7 10-3.8-1.8-7-5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span><span><strong>Secure access</strong><small>Only approved and published results are shown</small></span></div>
            </div>
            <div class="hero-visual" aria-label="Students learning together">
                <div class="photo-frame"><img src="images/srms-students-hero.jpg" alt="School students in uniform, ready to learn" width="579" height="384" fetchpriority="high"></div>
                <div class="floating-card result-card"><span class="floating-icon"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/></svg></span><span><strong>Results made simple</strong><small>Find your published results</small></span><span class="card-arrow">↗</span></div>
                <div class="floating-card notice-card"><span class="notice-pulse"></span><span><strong>Stay informed</strong><small>School news &amp; important dates</small></span></div>
                <div class="visual-caption"><span class="caption-line"></span> LEARN · GROW · ACHIEVE</div>
            </div>
        </div>
    </section>
    <section class="quick-strip" aria-label="Portal highlights"><div class="container quick-strip-inner"><div class="quick-item"><span class="quick-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg></span><span><strong>Results access</strong><small>Published academic records</small></span></div><div class="quick-item"><span class="quick-icon"><svg viewBox="0 0 24 24"><path d="M5 4h14v17l-7-4-7 4zM8 8h8M8 11h8"/></svg></span><span><strong>School notices</strong><small>Updates in one place</small></span></div><div class="quick-item"><span class="quick-icon"><svg viewBox="0 0 24 24"><path d="M12 3 20 7v5c0 4.5-3.3 7.4-8 9-4.7-1.6-8-4.5-8-9V7zM9 12l2 2 4-4"/></svg></span><span><strong>Trusted workflow</strong><small>Approved results only</small></span></div></div></section>
    <section class="section announcements-section" id="announcements">
        <div class="container">
            <div class="section-heading"><div><div class="section-kicker">WHAT'S HAPPENING</div><h2>Latest announcements</h2><p>Important updates and information from the school.</p></div><a class="text-link" href="index.php#announcements">School updates <span aria-hidden="true">→</span></a></div>
            <?php if (count($notices) > 0): ?>
                <div class="notice-grid">
                <?php foreach ($notices as $notice): ?>
                    <article class="notice-tile"><div class="notice-tile-top"><span class="notice-label"><span></span> SCHOOL NOTICE</span><span class="notice-date"><?php echo !empty($notice['postingDate']) ? htmlentities(date('d M Y', strtotime($notice['postingDate'])), ENT_QUOTES, 'UTF-8') : 'Announcement'; ?></span></div><h3><a href="notice-details.php?nid=<?php echo (int)$notice['id']; ?>"><?php echo htmlentities($notice['noticeTitle'] ?? 'School announcement', ENT_QUOTES, 'UTF-8'); ?></a></h3><p><?php echo htmlentities(landing_excerpt($notice['noticeDetails'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p><a class="tile-link" href="notice-details.php?nid=<?php echo (int)$notice['id']; ?>">Read announcement <span aria-hidden="true">→</span></a></article>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-notices"><span class="empty-icon"><svg viewBox="0 0 24 24"><path d="M5 4h14v17l-7-4-7 4zM8 8h8M8 11h8"/></svg></span><div><strong>No new announcements just yet</strong><p>When the school publishes a notice, it will appear here.</p></div></div>
            <?php endif; ?>
        </div>
    </section>
    <section class="portals-section" id="portals"><div class="container"><div class="section-heading section-heading-light"><div><div class="section-kicker">QUICK ACCESS</div><h2>How can we help you?</h2><p>Choose the service you need to get started.</p></div></div><div class="portal-grid">
        <a class="portal-card portal-card-featured" href="find-result.php"><span class="portal-icon"><svg viewBox="0 0 24 24"><path d="M4 4h12l4 4v12H4zM15 4v5h5M8 13h8M8 16h5"/></svg></span><span class="portal-tag">STUDENTS &amp; GUARDIANS</span><h3>Check examination results</h3><p>Look up results that have been reviewed, approved and published by the school.</p><span class="portal-action">Find my results <span aria-hidden="true">→</span></span><span class="portal-watermark" aria-hidden="true">01</span></a>
        <a class="portal-card" href="login.php"><span class="portal-icon"><svg viewBox="0 0 24 24"><path d="M3 5h18v13H3zM8 21h8M12 18v3M7 9h4M7 12h8"/></svg></span><span class="portal-tag">TEACHING STAFF</span><h3>Teacher portal</h3><p>Access assigned classes, manage marks and follow result submissions.</p><span class="portal-action">Teacher sign in <span aria-hidden="true">→</span></span><span class="portal-watermark" aria-hidden="true">02</span></a>
        <a class="portal-card" href="login.php"><span class="portal-icon"><svg viewBox="0 0 24 24"><path d="M12 3 20 7v5c0 4.5-3.3 7.4-8 9-4.7-1.6-8-4.5-8-9V7zM9 12l2 2 4-4"/></svg></span><span class="portal-tag">SCHOOL ADMINISTRATION</span><h3>Administration portal</h3><p>Manage students, classes, fees, academic periods and published results.</p><span class="portal-action">Admin sign in <span aria-hidden="true">→</span></span><span class="portal-watermark" aria-hidden="true">03</span></a>
    </div></div></section>
    <section class="closing-cta"><div class="container closing-inner"><div><div class="section-kicker">HERE TO HELP</div><h2>Everything you need to stay up to date.</h2><p>Start with results or explore the latest school announcements.</p></div><a class="button button-white" href="find-result.php">Check results <span aria-hidden="true">→</span></a></div></section>
</main>
<footer class="site-footer"><div class="container footer-main"><a class="brand footer-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M4 11.5 16 5l12 6.5L16 18 4 11.5Z"/><path d="M8 14v7c4.8 4.2 11.2 4.2 16 0v-7M28 12v9"/></svg></span><span class="brand-copy"><strong><?php echo htmlentities($schoolName, ENT_QUOTES, 'UTF-8'); ?></strong><small>STUDENT &amp; SCHOOL PORTAL</small></span></a><p>Supporting a connected, informed school community.</p><div class="footer-links"><a href="find-result.php">Check results</a><a href="login.php">Teacher login</a><a href="login.php">Administration</a></div></div><div class="footer-bottom"><div class="container"><span>© <?php echo date('Y'); ?> <?php echo htmlentities($schoolName, ENT_QUOTES, 'UTF-8'); ?>. All rights reserved.</span><span>Learn · Grow · Achieve</span></div></div></footer>
<script>
(function(){var toggle=document.querySelector('.nav-toggle');var nav=document.querySelector('.nav-links');if(toggle&&nav){toggle.addEventListener('click',function(){var expanded=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!expanded));nav.classList.toggle('is-open',!expanded);});nav.querySelectorAll('a').forEach(function(link){link.addEventListener('click',function(){toggle.setAttribute('aria-expanded','false');nav.classList.remove('is-open');});});}})();
if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('./service-worker.js').catch(function(){})});}
</script>
</body>
</html>
