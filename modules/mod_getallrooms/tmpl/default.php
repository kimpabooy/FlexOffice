<?php
defined('_JEXEC') or die;
?>
<div class="mod-getallrooms">
    <h3>Get All rooms</h3>
    <?php if (empty($rooms)) : ?>
        <p>Inga rum hittades eller ett fel uppstod.</p>
    <?php else : ?>
        <ul>
        <?php foreach ($rooms as $room) : ?>
            <?php
                // $room is an object returned by loadObjectList()
                $roomId = isset($room->id) ? (int) $room->id : '';
                $roomName = isset($room->name) ? htmlspecialchars($room->name, ENT_QUOTES, 'UTF-8') : '';
            ?>
            <li><?php echo $roomId . ' - ' . $roomName; ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
