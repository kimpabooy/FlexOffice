<?php
defined('_JEXEC') or die;
?>
<div class="mod-getallusers">
    <h3>Get All Users Module</h3>
    <?php if (empty($users)) : ?>
        <p>Inga användare hittades eller ett fel uppstod.</p>
    <?php else : ?>
        <!-- <ul> -->
        <?php foreach ($users as $u) : ?>
            <li><?php echo htmlspecialchars($u->name . ' (' . $u->username . ') - ' . $u->email); ?></li>
        <?php endforeach; ?>
        <!-- </ul> -->
    <?php endif; ?>
</div>
