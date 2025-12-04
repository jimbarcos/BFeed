<?php
// Team Section partial for about page

use BuzzarFeed\Utils\Helpers;
?>
<section class="team-section">
    <div class="team-container">
        <?php for ($i = 0; $i < 3 && $i < count($teamMembers); $i++): ?>
            <div class="team-member">
                <div class="member-photo">
                    <img src="assets/images/about/team/team-placeholder.png" alt="<?= Helpers::escape($teamMembers[$i]['name']) ?>">
                </div>
                <div class="member-info">
                    <p class="member-name"><?= Helpers::escape($teamMembers[$i]['name']) ?></p>
                    <p class="member-position"><?= Helpers::escape($teamMembers[$i]['position']) ?></p>
                </div>
            </div>
        <?php endfor; ?>
        
        <div class="team-title-box">
            <h2 class="team-title">
                Meet the<br>
                Team
            </h2>
        </div>

        <div class="team-star-1">
            <img src="assets/images/about/about-hexadecagon-2.png" alt="Orange star decoration">
        </div>
        
        <?php for ($i = 3; $i < 6 && $i < count($teamMembers); $i++): ?>
            <div class="team-member">
                <div class="member-photo">
                    <img src="assets/images/about/team/team-placeholder.png" alt="<?= Helpers::escape($teamMembers[$i]['name']) ?>">
                </div>
                <div class="member-info">
                    <p class="member-name"><?= Helpers::escape($teamMembers[$i]['name']) ?></p>
                    <p class="member-position"><?= Helpers::escape($teamMembers[$i]['position']) ?></p>
                </div>
            </div>
        <?php endfor; ?>
        
        <?php for ($i = 6; $i < 10 && $i < count($teamMembers); $i++): ?>
            <div class="team-member">
                <div class="member-photo">
                    <img src="assets/images/about/team/team-placeholder.png" alt="<?= Helpers::escape($teamMembers[$i]['name']) ?>">
                </div>
                <div class="member-info">
                    <p class="member-name"><?= Helpers::escape($teamMembers[$i]['name']) ?></p>
                    <p class="member-position"><?= Helpers::escape($teamMembers[$i]['position']) ?></p>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <div class="team-star-2">
        <img src="assets/images/about/about-hexadecagon-2.png" alt="Orange star decoration">
    </div>
</section>