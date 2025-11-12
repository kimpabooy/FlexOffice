<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

// $available is expected to be provided by the module entry (mod_calendar.php)
$user = Factory::getUser();

// Prepare calendar variables (week-based navigation)
$input = Factory::getApplication()->input;
$startParam = $input->getString('start', ''); // expected format YYYY-MM-DD

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
$weekStartLabel = htmlspecialchars(date('j M Y', $mondayTs), ENT_QUOTES, 'UTF-8');
$weekEndLabel = htmlspecialchars(date('j M Y', strtotime('+6 days', $mondayTs)), ENT_QUOTES, 'UTF-8');
?>

<div class="mod-calendar">
    <div class="mbc-nav" style="margin-bottom:6px;">
        <?php if ($showPrev): ?>
            <a class="btn btn-secondary btn-sm mod-calendar-week-link" href="?start=<?php echo date('Y-m-d', $prevTs); ?>">&larr; Föregående</a>
        <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled>&larr; Föregående</button>
        <?php endif; ?>
        <strong style="margin:0 10px;"><?php echo $weekStartLabel . ' — ' . $weekEndLabel; ?></strong>
        <a class="btn btn-secondary btn-sm mod-calendar-week-link" href="?start=<?php echo date('Y-m-d', $nextTs); ?>">Nästa &rarr;</a>
    </div>

    <table class="table table-bordered" style="table-layout:fixed;">
        <thead>
            <tr>
                <?php
                    $days = ['Måndag','Tisdag','Onsdag','Torsdag','Fredag','Lördag','Söndag'];
                    for ($i=0;$i<7;$i++) {
                        $dTs = strtotime('+' . $i . ' days', $mondayTs);
                        echo '<th>' . $days[$i] . '<br/>' . date('j/n', $dTs) . '</th>';
                    }
                ?>
            </tr>
        </thead>
        <tbody>
            <tr>
            <?php
                for ($i=0;$i<7;$i++) {
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
                            if (isset($p->room_name)) { $roomLabel = $p->room_name; }
                            elseif (isset($p->room)) { $roomLabel = $p->room; }
                            $roomLabelEsc = htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8');
                            // Show simple read-only representation — booking action belongs to mod_booking
                            echo '<span class="badge badge-light">' . (int)$p->desk_id . '</span> ' . $startTime . ' — ' . $endTime . ' <small>(' . $roomLabelEsc . ')</small>';
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

<script>
(function(){
    // Minimal client-side: AJAX week navigation to replace .mod-calendar fragment
    function attachHandlers(root){
        root = root || document;
        var links = root.getElementsByClassName('mod-calendar-week-link');
        for (var j=0;j<links.length;j++) {
            links[j].addEventListener('click', function(e){
                e.preventDefault();
                var href = e.currentTarget.getAttribute('href');
                if (!href) return;
                fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(resp){ return resp.text(); })
                    .then(function(html){
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = html;
                        var cal = wrapper.querySelector('.mod-calendar') || wrapper;
                        var existing = document.querySelector('.mod-calendar');
                        if (existing && cal) {
                            existing.parentNode.replaceChild(cal, existing);
                            attachHandlers(cal.parentNode);
                            if (history && history.pushState) {
                                var newUrl = href.indexOf('?') === 0 ? window.location.pathname + href : href;
                                history.pushState(null, '', newUrl);
                            }
                        }
                    }).catch(function(){ window.location.href = href; });
            });
        }
    }
    attachHandlers();
})();
</script>
