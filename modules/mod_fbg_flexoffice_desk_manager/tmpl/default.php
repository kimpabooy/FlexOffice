<?php
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

$rooms = ModFbgFlexofficeDeskManagerHelper::getRooms();
$desks = ModFbgFlexofficeDeskManagerHelper::getDesks();
$groups = ModFbgFlexofficeDeskManagerHelper::getGroups();
$locations = ModFbgFlexofficeDeskManagerHelper::getLocations();

?>

<div class="mod-desk-manager">
    <h3>Skapa en ny plats</h3>
    <form method="post">
        <label>Namn<br><input type="text" name="location_name" required placeholder="Ange ett namn..."></label><br>
        <input type="hidden" name="mod_task" value="create.location">
        <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
        <button type="submit">Skapa plats</button>
    </form>

    <hr>
    <h3>Skapa en ny avdelning</h3>
    <form method="post">
        <label>Plats<br>
            <select name="group_location_id" required>
                <option value="">-- Välj --</option>
                <?php foreach ($locations as $loc) : ?>
                    <option value="<?php echo (int) $loc->id; ?>"><?php echo htmlspecialchars($loc->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Avdelningsnamn<br><input type="text" name="group_name" required placeholder="Ange ett namn..."></label><br>
        <input type="hidden" name="mod_task" value="create.group">
        <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
        <button type="submit">Skapa avdelning</button>
    </form>

    <hr>

    <h3>Skapa ett nytt rum</h3>
    <form method="post">
        <label>Plats<br>
            <select id="room_location_select" required>
                <option value="">-- Välj plats --</option>
                <?php foreach ($locations as $loc) : ?>
                    <option value="<?php echo (int) $loc->id; ?>"><?php echo htmlspecialchars($loc->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Avdelning<br>
            <select name="room_group_id" id="room_group_select" required>
                <option value="">-- Välj avdelning --</option>
                <?php foreach ($groups as $g) : ?>
                    <option value="<?php echo (int) $g->id; ?>" data-location-id="<?php echo (int) $g->location_id; ?>" style="display: none;">
                        <?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Rumsnamn<br><input type="text" name="room_name" required placeholder="Ange ett rum..."></label><br>
        <input type="hidden" name="mod_task" value="create.room">
        <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
        <button type="submit">Skapa rum</button>
    </form>

    <hr>

    <h3>Skapa ett nytt skrivbord</h3>
    <form method="post">
        <label>Plats<br>
            <select id="desk_location_select" required>
                <option value="">-- Välj plats --</option>
                <?php foreach ($locations as $loc) : ?>
                    <option value="<?php echo (int) $loc->id; ?>"><?php echo htmlspecialchars($loc->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Avdelning<br>
            <select id="desk_group_select" required>
                <option value="">-- Välj avdelning --</option>
                <?php foreach ($groups as $g) : ?>
                    <option value="<?php echo (int) $g->id; ?>" data-location-id="<?php echo (int) $g->location_id; ?>" style="display: none;">
                        <?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Rum<br>
            <select name="desk_room_id" id="desk_room_select" required>
                <option value="">-- Välj rum --</option>
                <?php foreach ($rooms as $r) : ?>
                    <option value="<?php echo (int) $r->id; ?>" data-group-id="<?php echo (int) $r->location_group_id; ?>" style="display: none;"><?php echo htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Skrivbordsnamn<br><input type="text" name="desk_name" required placeholder="Ange ett skrivbord..."></label><br>
        <input type="hidden" name="mod_task" value="create.desk">
        <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
        <button type="submit">Skapa skrivbord</button>
    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // RUM FORMULÄR
        const roomLocationSelect = document.getElementById('room_location_select');
        const roomGroupSelect = document.getElementById('room_group_select');
        const roomGroupOptions = roomGroupSelect.querySelectorAll('option[data-location-id]');

        // När plats ändras i rumsformuläret, uppdatera avdelningslistan
        roomLocationSelect.addEventListener('change', function() {
            const selectedLocationId = this.value;
            roomGroupSelect.value = '';
            roomGroupOptions.forEach(function(option) {
                option.style.display = 'none';
            });
            // Visa endast avdelningar som tillhör den valda platsen
            if (selectedLocationId) {
                roomGroupOptions.forEach(function(option) {
                    if (option.getAttribute('data-location-id') === selectedLocationId) {
                        option.style.display = '';
                    }
                });
            }
        });

        // SKRIVBORD FORMULÄR
        const deskLocationSelect = document.getElementById('desk_location_select');
        const deskGroupSelect = document.getElementById('desk_group_select');
        const deskGroupOptions = deskGroupSelect.querySelectorAll('option[data-location-id]');
        const deskRoomSelect = document.getElementById('desk_room_select');
        const deskRoomOptions = deskRoomSelect.querySelectorAll('option[data-group-id]');

        // När plats ändras i skrivbordsformuläret, uppdatera avdelningslistan
        deskLocationSelect.addEventListener('change', function() {
            const selectedLocationId = this.value;
            deskGroupSelect.value = '';
            deskGroupOptions.forEach(function(option) {
                option.style.display = 'none';
            });
            deskRoomSelect.value = '';
            deskRoomOptions.forEach(function(option) {
                option.style.display = 'none';
            });
            // Visa endast avdelningar som tillhör den valda platsen
            if (selectedLocationId) {
                deskGroupOptions.forEach(function(option) {
                    if (option.getAttribute('data-location-id') === selectedLocationId) {
                        option.style.display = '';
                    }
                });
            }
        });

        // När avdelning ändras i skrivbordsformuläret, uppdatera rumslistan
        deskGroupSelect.addEventListener('change', function() {
            const selectedGroupId = this.value;
            deskRoomSelect.value = '';
            deskRoomOptions.forEach(function(option) {
                option.style.display = 'none';
            });
            // Visa endast rum som tillhör den valda avdelningen
            if (selectedGroupId) {
                deskRoomOptions.forEach(function(option) {
                    if (option.getAttribute('data-group-id') === selectedGroupId) {
                        option.style.display = '';
                    }
                });
            }
        });
    });
</script>