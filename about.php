<?php
/**
 * BuzzarFeed - About Page
 * 
 * Learn about BuzzarFeed and meet the team
 * 
 * @package BuzzarFeed
 * @version 1.0
 */

require_once __DIR__ . '/bootstrap.php';

use BuzzarFeed\Utils\Helpers;
use BuzzarFeed\Utils\Session;

Session::start();

$pageTitle = "About Us - BuzzarFeed";
$pageDescription = "Everything you need to know about BuzzarFeed - Your digital guide to the flavors of the BGC Night Market Bazaar";
 
$teamMembers = [
    ['name' => 'Chrysler Dele B. Ordas', 'position' => 'Project Manager'],
    ['name' => 'Jimalyn B. Del Rosario', 'position' => 'Business Analyst'],
    ['name' => 'Princess Jane C. Drama', 'position' => 'UI/UX Designer'],
    ['name' => 'Jim Aerol Barcos', 'position' => 'Backend Developer'],
    ['name' => 'Acelle Krislette L. Rosales', 'position' => 'Backend Developer'],
    ['name' => 'Regina S. Bonifacio', 'position' => 'Backend Developer'],
    ['name' => 'John Lloyd S. Legaspi', 'position' => 'Frontend Developer'],
    ['name' => 'Kyla Mae N. Valoria', 'position' => 'Frontend Developer'],
    ['name' => 'Xavier B. Rolle', 'position' => 'Quality Assurance Engineer'],
    ['name' => 'Ernest Matthew L. Maravilla', 'position' => 'Documentation Specialist'],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Helpers::escape($pageDescription) ?>">
    <title><?= Helpers::escape($pageTitle) ?></title>
    
    <link rel="icon" type="image/png" href="<?= IMAGES_URL ?>/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= CSS_URL ?>/variables.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/base.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/button.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/components/dropdown.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/styles.css">
    <link rel="stylesheet" href="<?= CSS_URL ?>/about.css">
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main>
        <?php include __DIR__ . '/sections/about/HeroSection.php'; ?>
        <hr class="section-divider" />
        <?php include __DIR__ . '/sections/about/AboutSection.php'; ?>
        <hr class="section-divider" />
        <?php include __DIR__ . '/sections/about/TeamSection.php'; ?>
        <hr class="section-divider" />
        <?php include __DIR__ . '/sections/about/CTASection.php'; ?>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script type="module" src="<?= JS_URL ?>/app.js"></script>
</body>
</html>