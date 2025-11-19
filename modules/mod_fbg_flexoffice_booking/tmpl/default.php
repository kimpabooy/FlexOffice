<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_fbg_flexoffice_booking
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

// $available and $bookings are provided by helper
?>
<div class="mod-booking">
    <?php if ($params->get('show_booking', 1)): ?>
        <h3>Boka skrivbord</h3>

        <?php $user = Factory::getApplication()->getIdentity(); ?>

        <?php if (!$user->id): ?>
            <p class="alert alert-warning">Du måste vara inloggad för att boka.</p>
        <?php endif; ?>



        <form method="post" action="" class="mod-booking-form">
            <?= HTMLHelper::_('form.token'); ?>
            <input type="hidden" name="task" value="book" />
            <input type="hidden" name="period_id" id="mod_fbg_flexoffice_booking_period_id" value="" />

            <div>
                <label for="booking-period"></label>
                <?php
                // Hämta start-parametern
                $input = Factory::getApplication()->input;
                $startParam = $input->post->getString('start', '');
                if (!$startParam) {
                    $startParam = $input->getString('start', '');
                }

                // Hämta kalenderdata från hjälparen
                $calendarData = ModFbgFlexofficeBookingHelper::getCalendarData($available, $startParam);
                // Gör variabler tillgängliga för calendar.php
                extract($calendarData);

                // Inkludera kalenderlayout
                $layoutPath = \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_booking', 'calendar');
                if ($layoutPath && file_exists($layoutPath)) {
                    include $layoutPath;
                }
                ?>
            </div>

            <!-- Simple modal for booking details -->
            <div id="mod-booking-modal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;">
                <div style="background:#fff; max-width:420px; margin:80px auto; padding:16px; border-radius:4px; box-shadow:0 6px 18px rgba(0,0,0,0.2);">
                    <h4 style="margin-top:0;">Bokningsdetaljer</h4>
                    <div style="margin-bottom:8px;"><strong>Plats:</strong> <span id="mod-booking-modal-location"></span></div>
                    <div style="margin-bottom:8px;"><strong>Avdelning:</strong> <span id="mod-booking-modal-location-group"></span></div>
                    <div style="margin-bottom:8px;"><strong>Rum:</strong> <span id="mod-booking-modal-room"></span></div>
                    <div style="margin-bottom:8px;"><strong>Skrivbord:</strong> <span id="mod-booking-modal-desk"></span></div>
                    <div style="margin-bottom:12px;"><strong>Tid:</strong> <span id="mod-booking-modal-time"></span></div>
                    <div style="margin-bottom:12px;"><strong>Kommentar:</strong> <span id="mod-booking-modal-comment"></span></div>
                    <div style="text-align:right;">
                        <button type="button" id="mod-booking-modal-cancel" class="btn btn-secondary btn-sm" style="margin-right:8px;">Avbryt</button>
                        <button type="button" id="mod-booking-modal-confirm" class="btn btn-primary btn-sm">Boka</button>
                    </div>
                </div>
            </div>

        </form>

        <script>
            (function() {
                var modal = document.getElementById('mod-booking-modal');
                var deskElement = document.getElementById('mod-booking-modal-desk');
                var roomElement = document.getElementById('mod-booking-modal-room');
                var locationGroupElement = document.getElementById('mod-booking-modal-location-group');
                var locationElement = document.getElementById('mod-booking-modal-location');
                var timeElement = document.getElementById('mod-booking-modal-time');
                var cancelBtn = document.getElementById('mod-booking-modal-cancel');
                var confirmBtn = document.getElementById('mod-booking-modal-confirm');
                var periodInput = document.getElementById('mod_fbg_flexoffice_booking_period_id');
                var commentElement = document.getElementById('mod-booking-modal-comment');
                var currentPeriodId = null;

                function openModal(data) {
                    deskElement.textContent = data.desk || '';
                    roomElement.textContent = data.room || '';
                    locationGroupElement.textContent = data.locationGroup || '';
                    locationElement.textContent = data.location || '';
                    commentElement.textContent = data.comment || 'N/A';
                    // Format times: show full provided timestamps or fallback to provided short
                    if (data.start && data.end) {
                        var s = data.start.slice(0, -3);
                        var e = data.end.slice(0, -3);
                        timeElement.textContent = s + ' — ' + e;
                    } else {
                        timeElement.textContent = (data.startShort || '') + (data.endShort ? ' — ' + data.endShort : '');
                    }
                    currentPeriodId = data.periodId;
                    modal.style.display = 'block';
                }

                function closeModal() {
                    modal.style.display = 'none';
                    currentPeriodId = null;
                }

                function attachHandlers(root) {
                    root = root || document;
                    // Attach click handlers to open buttons
                    var opens = root.getElementsByClassName('mod-booking-open');
                    for (var i = 0; i < opens.length; i++) {
                        opens[i].addEventListener('click', function(e) {
                            var btn = e.currentTarget;
                            var data = {
                                periodId: btn.getAttribute('data-period-id'),
                                desk: btn.getAttribute('data-desk'),
                                room: btn.getAttribute('data-room'),
                                locationGroup: btn.getAttribute('data-location-group'),
                                location: btn.getAttribute('data-location'),
                                comment: btn.getAttribute('data-description'),
                                start: btn.getAttribute('data-start'),
                                end: btn.getAttribute('data-end'),
                                startShort: btn.textContent
                            };
                            openModal(data);
                        });
                    }

                    // Week navigation links (AJAX)
                    var links = root.getElementsByClassName('mod-booking-week-link');
                    for (var j = 0; j < links.length; j++) {
                        links[j].addEventListener('click', function(e) {
                            e.preventDefault();
                            var el = e.currentTarget;
                            var date = el.getAttribute('data-start');
                            if (!date) return;
                            // POST the start date to the current path so the server can return the calendar fragment
                            var url = window.location.pathname;
                            var body = new URLSearchParams();
                            body.append('start', date);
                            fetch(url, {
                                    method: 'POST',
                                    body: body,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(function(resp) {
                                    return resp.text();
                                })
                                .then(function(html) {
                                    var wrapper = document.createElement('div');
                                    wrapper.innerHTML = html;
                                    var cal = wrapper.querySelector('.mod-booking-calendar') || wrapper;
                                    var existing = document.querySelector('.mod-booking-calendar');
                                    if (existing && cal) {
                                        existing.parentNode.replaceChild(cal, existing);
                                        // Re-attach handlers for newly inserted elements
                                        attachHandlers(cal.parentNode);
                                        // We intentionally do NOT update the browser URL; navigation is POST-based
                                    }
                                }).catch(function(err) {
                                    // On error, fall back to GET navigation so it still works without JS
                                    window.location.href = window.location.pathname + '?start=' + encodeURIComponent(date);
                                });
                        });
                    }
                }

                // initial attach
                attachHandlers();

                cancelBtn.addEventListener('click', function() {
                    closeModal();
                });

                confirmBtn.addEventListener('click', function() {
                    if (!currentPeriodId) return;
                    if (periodInput) {
                        periodInput.value = currentPeriodId;
                    }
                    // submit the form
                    var form = document.querySelector('.mod-booking-form');
                    if (form) {
                        form.submit();
                    }
                });

                // Close when clicking outside modal content
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });
            })();
        </script>
    <?php endif; ?>

    <?php if ($params->get('show_existing', 1)): ?>
        <h4>Befintliga bokningar</h4>
        <ul class="mod-booking-list">
            <?php if (empty($bookings)): ?>
                <li>Inga bokningar än</li>
            <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                    <?php
                    $displayUser = '';
                    if (!empty($b->user_name)) {
                        $displayUser = htmlspecialchars($b->user_name, ENT_QUOTES, 'UTF-8');
                    } else {
                        $displayUser = 'ID ' . ((int)$b->user_id);
                    }
                    ?>
                    <?php
                    // Format start/end times without seconds (YYYY-MM-DD HH:MM)
                    $startFmt = '';
                    $endFmt = '';
                    if (!empty($b->start_time) && strtotime($b->start_time) !== false) {
                        $startFmt = htmlspecialchars(date('Y-m-d H:i', strtotime($b->start_time)), ENT_QUOTES, 'UTF-8');
                    }
                    if (!empty($b->end_time) && strtotime($b->end_time) !== false) {
                        $endFmt = htmlspecialchars(date('Y-m-d H:i', strtotime($b->end_time)), ENT_QUOTES, 'UTF-8');
                    }
                    ?>
                    <li>
                        <?php echo 'Skrivbord ' . ((int)$b->desk_id) . ' — ' . $startFmt . ' till ' . $endFmt . ' (Bokad för: ' . $displayUser . ')'; ?>
                        <form method="post" action="" style="display: inline; margin-left: 10px;">
                            <?= HTMLHelper::_('form.token'); ?>
                            <input type="hidden" name="task" value="cancel" />
                            <input type="hidden" name="booking_id" value="<?= (int) $b->id ?>" />
                            <button type="submit" class="btn btn-danger btn-sm">Avboka</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <?php if ($params->get('show_personal_bookings', 0) && !empty($personalBookings)): ?>
        <!-- <h4>Mina bokningar</h4> -->
        <ul class="mod-booking-list-personal">
            <?php foreach ($personalBookings as $b): ?>
                <?php
                // Format start/end times without seconds (YYYY-MM-DD HH:MM)
                $startFmt = '';
                $endFmt = '';
                if (!empty($b->start_time) && strtotime($b->start_time) !== false) {
                    $startFmt = htmlspecialchars(date('Y-m-d H:i', strtotime($b->start_time)), ENT_QUOTES, 'UTF-8');
                }
                if (!empty($b->end_time) && strtotime($b->end_time) !== false) {
                    $endFmt = htmlspecialchars(date('Y-m-d H:i', strtotime($b->end_time)), ENT_QUOTES, 'UTF-8');
                }
                ?>
                <li>
                    <?php echo 'Skrivbord ' . ((int)$b->desk_id) . ' — ' . $startFmt . ' till ' . $endFmt; ?>
                    <form method="post" action="" style="display: inline; margin-left: 10px;">
                        <?= HTMLHelper::_('form.token'); ?>
                        <input type="hidden" name="task" value="cancel" />
                        <input type="hidden" name="booking_id" value="<?= (int) $b->id ?>" />
                        <button type="submit" class="btn btn-danger btn-sm">Avboka</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($params->get('show_personal_bookings', 0)): ?>
        <h4>Mina bokningar</h4>
        <p>Du har inga kommande bokningar.</p>
    <?php endif; ?>
</div>