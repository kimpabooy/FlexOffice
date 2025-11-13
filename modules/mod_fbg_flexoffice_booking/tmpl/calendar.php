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

// Prepare calendar variables (week-based navigation)
$input = Factory::getApplication()->input;
// Prefer POST 'start' when navigation is done via AJAX POST; fall back to GET query param
$startParam = $input->post->getString('start', ''); // expected format YYYY-MM-DD
if (!$startParam) {
    $startParam = $input->getString('start', '');
}

// build periods by day
$periodsByDay = [];
if (!empty($available) && is_array($available)) {
    foreach ($available as $p) {
        if (empty($p->start_time)) continue;
        $ts = strtotime($p->start_time);
        $d = date('Y-m-d', $ts);
        $periodsByDay[$d][] = $p;
    }
}

// Determine current week's Monday timestamp based on startParam or today
if ($startParam && strtotime($startParam) !== false) {
    $refTs = strtotime($startParam);
} else {
    $refTs = time();
}
$dayOfWeek = (int) date('N', $refTs); // 1 (Mon) - 7 (Sun)
$mondayTs = strtotime('-' . ($dayOfWeek - 1) . ' days', $refTs);

// Prev/Next move by one week
$prevTs = strtotime('-7 days', $mondayTs);
$nextTs = strtotime('+7 days', $mondayTs);

// Do not allow navigating earlier than the current week's Monday
$minMondayTs = strtotime('monday this week');
$showPrev = ($prevTs >= $minMondayTs);

// Display label for current week
// mondayTs means the Monday of the current displayed week
$weekStartLabel = htmlspecialchars(date('j M Y', $mondayTs), ENT_QUOTES, 'UTF-8');
$weekEndLabel = htmlspecialchars(date('j M Y', strtotime('+6 days', $mondayTs)), ENT_QUOTES, 'UTF-8');
?>

<div class="mod-booking-calendar">
    <div class="mbc-nav" style="margin-bottom: 10px; text-align: center;">
        <?php if ($showPrev): ?>
            <a class="btn btn-secondary btn-sm mod-booking-week-link" href="#" data-start="<?php echo date('Y-m-d', $prevTs); ?>">&larr; Föregående</a>
        <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled>&larr; Föregående</button>
        <?php endif; ?>
        <strong style="margin:0 10px;"><?php echo $weekStartLabel . ' — ' . $weekEndLabel; ?></strong>

        <a class="btn btn-secondary btn-sm mod-booking-week-link" href="#" data-start="<?php echo date('Y-m-d', $nextTs); ?>">Nästa &rarr;</a>
    </div>

    <table class="table table-bordered" style="table-layout:fixed;">
        <thead>
            <tr>
                <?php
                $days = ['Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag', 'Söndag'];
                for ($i = 0; $i < 7; $i++) {
                    $dTs = strtotime('+' . $i . ' days', $mondayTs);
                    echo '<th>' . $days[$i] . '<br/>' . date('j/n', $dTs) . '</th>';
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php
                for ($i = 0; $i < 7; $i++) {
                    $dTs = strtotime('+' . $i . ' days', $mondayTs);
                    $currentDate = date('Y-m-d', $dTs);
                    echo '<td style="vertical-align:top; padding:6px;">';
                    if (!empty($periodsByDay[$currentDate])) {
                        foreach ($periodsByDay[$currentDate] as $p) {
                            $startTs = strtotime($p->start_time);
                            $endTs = !empty($p->end_time) ? strtotime($p->end_time) : null;
                            $startTime = $startTs ? htmlspecialchars(date('H:i', $startTs), ENT_QUOTES, 'UTF-8') : '';
                            $endTime = $endTs ? htmlspecialchars(date('H:i', $endTs), ENT_QUOTES, 'UTF-8') : '';
                            echo '<div style="font-size:90%; margin-top:4px;">';
                            $roomLabel = '';
                            if (isset($p->room_name)) {
                                $roomLabel = $p->room_name;
                            } elseif (isset($p->room)) {
                                $roomLabel = $p->room;
                            }
                            $roomLabelEsc = htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8');
                            if ($user->id) {
                                echo '<button type="button" class="btn btn-secondary btn-sm mod-booking-open" '
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
                    echo '</td>';
                }
                ?>
            </tr>
        </tbody>
    </table>
</div>