<?php
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

?>
<div class="mod-personal-desk-availability">
    <h3>Sätt skrivbord som tillgängligt</h3>

    <form id="mod_personal_desk_availability_form" method="post" style="gap: 10px; display: flex; flex-direction: column;">
        <div>
            <label for="desk_id">Välj skrivbord</label>
            <select name="desk_id" id="desk_id">
                <?php foreach ($desks as $d) : ?>
                    <option value="<?php echo (int) $d->id; ?>"><?php echo 'Skrivbord #' . (int) $d->id . ' — ' . htmlspecialchars($d->room_name ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="label">Kommentar (valfritt)</label>
            <input type="text" name="label" id="label" placeholder="Kort kommentar">
        </div>

        <!-- <div>
            <label for="start_date">Från</label>
            <input type="date" name="start_date" id="start_date" required>
            <label for="start_time"></label>
            <input type="time" name="start_time" id="start_time" required>
        </div>

        <div>
            <label for="end_date">Till</label>
            <input type="date" name="end_date" id="end_date">
            <label for="end_time"></label>
            <input type="time" name="end_time" id="end_time">
        </div> -->

        <div>
            <h5>Välj typ av tillgänglighet</h5>
            <label>
                <input type="radio" name="availability_mode" value="range" id="mode_range" checked>
                Boka ett intervall (från - till)
            </label>
            <div></div>
            <label>
                <input type="radio" name="availability_mode" value="weekday" id="mode_weekday">
                Upprepa veckodagar (hela dagen 08:00–17:00)
            </label>
        </div>

        <div id="rangeFields">
            <h6>Ange intervall</h6>
            <div>
                <label for="start_date">Från</label>
                <input type="date" name="start_date" id="start_date" required>
                <label for="start_time"></label>
                <input type="time" name="start_time" id="start_time" required>
            </div>

            <div>
                <label for="end_date">Till</label>
                <input type="date" name="end_date" id="end_date">
                <label for="end_time"></label>
                <input type="time" name="end_time" id="end_time">
            </div>
        </div>

        <div id="weekdayFields" style="display:none;">
            <h6>Välj veckodag och antal veckor framåt</h6>
            <label for="repeat_weekday"></label>
            <select name="repeat_weekday" id="repeat_weekday">
                <option value="0">(Välj veckodag)</option>
                <option value="1">Måndag</option>
                <option value="2">Tisdag</option>
                <option value="3">Onsdag</option>
                <option value="4">Torsdag</option>
                <option value="5">Fredag</option>
                <option value="6">Lördag</option>
                <option value="7">Söndag</option>
            </select>
            <label for="repeat_count">Antal veckor</label>
            <input type="number" name="repeat_count" id="repeat_count" min="1" value="1" style="width:5em;">
        </div>

        <div>
            <input type="hidden" name="task" value="personaldesk.save" />
            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1" />
        </div>

        <script>
            (function() {
                var rangeFields = document.getElementById('rangeFields');
                var weekdayFields = document.getElementById('weekdayFields');
                var modeRange = document.getElementById('mode_range');
                var modeWeekday = document.getElementById('mode_weekday');

                function updateVisibility() {
                    if (modeWeekday.checked) {
                        rangeFields.style.display = 'none';
                        weekdayFields.style.display = '';
                        // make time fields not required
                        var st = document.getElementById('start_time');
                        var et = document.getElementById('end_time');
                        if (st) {
                            st.required = false;
                        }
                        if (et) {
                            et.required = false;
                        }
                        // start_date is hidden in this mode; remove required so browser doesn't block submit
                        var sd = document.getElementById('start_date');
                        if (sd) {
                            sd.required = false;
                            // ensure there's a sensible reference date for server-side (use today)
                            var d = new Date();
                            var yyyy = d.getFullYear();
                            var mm = String(d.getMonth() + 1).padStart(2, '0');
                            var dd = String(d.getDate()).padStart(2, '0');
                            sd.value = yyyy + '-' + mm + '-' + dd;
                        }
                    } else {
                        rangeFields.style.display = '';
                        weekdayFields.style.display = 'none';
                        // restore required attributes for visible fields
                        var st = document.getElementById('start_time');
                        if (st) {
                            st.required = true;
                        }
                        var sd = document.getElementById('start_date');
                        if (sd) {
                            sd.required = true;
                        }
                        // end_time optional
                    }
                }

                modeRange.addEventListener('change', updateVisibility);
                modeWeekday.addEventListener('change', updateVisibility);
                updateVisibility();
            })();
        </script>

        <script>
            (function() {
                var form = document.getElementById('mod_personal_desk_availability_form');
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    // If weekday mode is selected, ensure a weekday is chosen
                    var modeWeek = document.getElementById('mode_weekday');
                    if (modeWeek && modeWeek.checked) {
                        var rw = document.getElementById('repeat_weekday');
                        if (rw && parseInt(rw.value, 10) === 0) {
                            // show a user-visible message and prevent submit
                            alert('Vänligen välj en veckodag.');
                            e.preventDefault();
                            return false;
                        }
                    }

                    // Disable submit button to avoid accidental double submits
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = 'Sparar...';
                    }
                    // allow normal submit to proceed
                }, {
                    passive: false
                });
            })();
        </script>

        <button type="submit">Spara</button>
    </form>
</div>