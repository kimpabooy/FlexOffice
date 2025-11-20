<?php
defined('_JEXEC') or die;

?>
<div class="mod-desk-availability-period">
    <h3>Lediga Skrivbord</h3>

    <?php if (empty($periods)) : ?>
        <p>Inga lediga skrivbord hittades.</p>
    <?php else : ?>

        <ul class="dap-list">
            <?php foreach ($periods as $p) : ?>
                <?php
                // Defensive helpers
                // Only treat a value as a desk id if the helper provided it in desk_id.
                // Do NOT fall back to the period id (that was causing incorrect #numbers).
                $deskId = isset($p->desk_id) && $p->desk_id !== '' ? (int) $p->desk_id : null;
                $room = isset($p->room_name) ? htmlspecialchars($p->room_name, ENT_QUOTES, 'UTF-8') : '';
                $label = isset($p->name) ? htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') : '';
                $createdBy = isset($p->created_by_name) ? htmlspecialchars($p->created_by_name, ENT_QUOTES, 'UTF-8') : (isset($p->created_by) ? htmlspecialchars($p->created_by, ENT_QUOTES, 'UTF-8') : '');

                // Date formatting with Swedish short month names
                $swMonths = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
                $fmt = function ($dt) use ($swMonths) {
                    if (empty($dt)) return '';
                    $ts = strtotime($dt);
                    if ($ts === false) return htmlspecialchars($dt, ENT_QUOTES, 'UTF-8');
                    $day = date('j', $ts);
                    $month = $swMonths[(int)date('n', $ts) - 1];
                    $year = date('Y', $ts);
                    $time = date('H:i', $ts);
                    return $day . ' ' . $month . ' ' . $year . ($time ? ' ' . $time : '');
                };
                $from = $fmt($p->start_time ?? $p->start_datetime ?? '');
                $to = $fmt($p->end_time ?? $p->end_datetime ?? '');
                ?>
                <li class="dap-item">
                    <div class="dap-title">
                        <?php if ($deskId !== null) : ?>
                            <?php echo 'Skrivbord #' . $deskId; ?>
                            <?php if ($room) : ?>
                                <?php echo ' — i ' . $room; ?>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php echo $label ?: ('Period #' . ((int) ($p->id ?? 0))); ?>
                        <?php endif; ?>
                    </div>
                    <div class="dap-meta">
                        <?php if ($label) : ?>
                            <div><?php echo $label; ?></div>
                        <?php endif; ?>
                        <div>
                            <?php if ($from) : ?>
                                Finns tillgänglig <?php echo $from; ?>
                                <?php if ($to) : ?> — <?php echo $to; ?><?php endif; ?>
                                <?php else : ?>
                                    Tid ej angiven
                                <?php endif; ?>
                        </div>
                        <?php if ($createdBy) : ?>
                            <div>Skapad av <?php echo $createdBy; ?></div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>