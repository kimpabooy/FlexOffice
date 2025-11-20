<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

// Respect module parameter: if booking form/calendar is hidden, render nothing (also covers AJAX requests)
if (isset($params) && $params->get('show_booking', 1) == 0) {
    return;
}

// $available is expected to be provided by the module entry (mod_fbg_flexoffice_booking.php)
$user = Factory::getApplication()->getIdentity();

?>

<div class="mod-booking-calendar">
    <div class="mbc-nav" style="margin-bottom: 10px; text-align: center;">
        <?php if ($showPrev): ?>
            <a class="btn btn-secondary btn-sm mod-booking-week-link" href="#" data-start="<?php echo date('Y-m-d', $prevTs); ?>">&larr; Föregående</a>
        <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled>&larr; Föregående</button>
        <?php endif; ?>
        <strong style="margin:0 10px;">
            <?php echo $weekStartLabel . ' — ' . $weekEndLabel; ?>
        </strong>
        <a class="btn btn-secondary btn-sm mod-booking-week-link" href="#" data-start="<?php echo date('Y-m-d', $nextTs); ?>">Nästa &rarr;</a>
    </div>

    <table class="table table-bordered" style="table-layout:fixed;">
        <thead>
            <tr>
                <?php foreach ($weekDates as $day): ?>
                    <th><?php echo $day['label']; ?><br /><?php echo $day['display']; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ($weekDates as $day): ?>
                    <td style="vertical-align:top; margin:0; padding:0px;">
                        <?php
                        $currentDate = $day['date'];
                        if (!empty($periodsByDay[$currentDate])) {
                            foreach ($periodsByDay[$currentDate] as $p) {
                                $startTs = strtotime($p->start_time);
                                $endTs = !empty($p->end_time) ? strtotime($p->end_time) : null;
                                $startTime = $startTs ? htmlspecialchars(date('H:i', $startTs), ENT_QUOTES, 'UTF-8') : '';
                                $endTime = $endTs ? htmlspecialchars(date('H:i', $endTs), ENT_QUOTES, 'UTF-8') : '';
                                // echo '<div style="font-size:90%; margin-top:4px;">';
                                $roomLabel = '';
                                if (isset($p->room_name)) {
                                    $roomLabel = $p->room_name;
                                } elseif (isset($p->room)) {
                                    $roomLabel = $p->room;
                                }
                                $roomLabelEsc = htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8');
                                if ($user->id) {
                                    echo '<button type="button" class="mod-booking-open" '
                                        . 'data-period-id="' . (int)$p->id . '" '
                                        . 'data-desk="' . htmlspecialchars((int)$p->desk_id, ENT_QUOTES, 'UTF-8') . '" '
                                        . 'data-room="' . $roomLabelEsc . '" '
                                        . 'data-location-group="' . htmlspecialchars($p->location_group_name, ENT_QUOTES, 'UTF-8') . '" '
                                        . 'data-location="' . htmlspecialchars($p->location_name, ENT_QUOTES, 'UTF-8') . '" '
                                        . 'data-description="' . htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') . '" '
                                        . 'data-start="' . htmlspecialchars($p->start_time, ENT_QUOTES, 'UTF-8') . '" '
                                        . 'data-end="' . htmlspecialchars($p->end_time ?: '', ENT_QUOTES, 'UTF-8') . '">'
                                        . '<strong>' . (int)$p->desk_id . '</strong> ' . $startTime . '-' . $endTime
                                        . '</button>';
                                } else {
                                    echo ' ' . $startTime;
                                    echo ' — ' . $endTime;
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
</div>