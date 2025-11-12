<?php
defined('_JEXEC') or die;
?>
<div class="mod-getallbookings">
    <h3>All Bookings</h3>
    <?php if (empty($bookings)) : ?>
        <p>Inga bokningar hittades eller ett fel uppstod.</p>
    <?php else : ?>
        <ul>
        <?php foreach ($bookings as $b) : ?>
            <?php
                $id = isset($b->id) ? (int)$b->id : '';
                $deskId = isset($b->desk_id) ? (int)$b->desk_id : '';
                $userId = isset($b->user_id) ? (int)$b->user_id : '';
                $userName = isset($b->user_name) ? $b->user_name : '';
                $start = isset($b->start_time) ? $b->start_time : '';
                $end = isset($b->end_time) ? $b->end_time : '';

                // Försök formatera tid om möjligt
                $startText = $start ? htmlspecialchars(date('Y-m-d H:i', strtotime($start)), ENT_QUOTES, 'UTF-8') : '';
                $endText = $end ? htmlspecialchars(date('Y-m-d H:i', strtotime($end)), ENT_QUOTES, 'UTF-8') : '';
            ?>
            <li>
                <?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?> — desk: <?php echo htmlspecialchars($deskId, ENT_QUOTES, 'UTF-8'); ?> — user: <?php echo $userName ? htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') : htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?> — från: <?php echo $startText; ?> — till: <?php echo $endText; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
