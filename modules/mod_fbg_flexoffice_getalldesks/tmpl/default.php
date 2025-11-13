<?php
defined('_JEXEC') or die;
?>
<div class="mod-getalldesks">
    <h3>All Desks</h3>
    <?php if (empty($desks)) : ?>
        <p>Inga skrivbord hittades eller ett fel uppstod.</p>
    <?php else : ?>
        <ul>
        <?php foreach ($desks as $desk) : ?>
            <?php
                $id = isset($desk->id) ? (int)$desk->id : '';
                $roomName = isset($desk->room_name) ? htmlspecialchars($desk->room_name, ENT_QUOTES, 'UTF-8') : '';
            ?>
            <li><?php echo 'Skrivbord # ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' — Rum: ' . htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
