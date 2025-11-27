<?php
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

$locations = ModFbgFlexofficeDeskManagerCombinedHelper::getLocations();
$groups = ModFbgFlexofficeDeskManagerCombinedHelper::getGroups();
$rooms = ModFbgFlexofficeDeskManagerCombinedHelper::getRooms();

?>
<div class="mod-desk-manager-combined">
    <h3>Skapa Nytt</h3>


    <div class="mod-desk-manager-combined-box">
        <!-- Plats -->
        <form method="post" id="createLocationForm" style="margin-bottom: 0;">
            <label>Plats
                <select id="location_select" name="group_location_id">
                    <option value="">-- Välj plats --</option>
                    <?php foreach ($locations as $loc) : ?>
                        <option value="<?php echo (int) $loc->id; ?>"> <?php echo htmlspecialchars($loc->name, ENT_QUOTES, 'UTF-8'); ?> </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="new_location_btn" class="ny-btn">Ny</button>
            </label>
            <div id="new_location_div" style="display:none; margin-top: 5px;">
                <input type="text" name="location_name" placeholder="Ny plats..." required>
                <input type="hidden" name="mod_task" value="create.location">
                <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                <button type="submit">Skapa plats</button>
                <button type="button" id="cancel_location_btn">Avbryt</button>
            </div>
        </form>
        <!-- Avdelning -->
        <form method="post" id="createGroupForm" style="margin-bottom: 0;">
            <label>Avdelning
                <select id="group_select" name="room_group_id">
                    <option value="">-- Välj avdelning --</option>
                    <?php foreach ($groups as $g) : ?>
                        <option value="<?php echo (int) $g->id; ?>" data-location-id="<?php echo (int) $g->location_id; ?>"> <?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?> </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="new_group_btn" class="ny-btn">Ny</button>
            </label>
            <div id="new_group_div" style="display:none; margin-top: 5px;">
                <input type="text" name="group_name" placeholder="Ny avdelning..." required>
                <input type="hidden" name="group_location_id" id="new_group_location_id">
                <input type="hidden" name="mod_task" value="create.group">
                <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                <button type="submit" id="create_group_btn">Skapa avdelning</button>
                <button type="button" id="cancel_group_btn">Avbryt</button>
            </div>
        </form>
        <!-- Rum -->
        <form method="post" id="createRoomForm" style="margin-bottom: 0;">
            <label>Rum
                <select id="room_select" name="desk_room_id">
                    <option value="">-- Välj rum --</option>
                    <?php foreach ($rooms as $r) : ?>
                        <option value="<?php echo (int) $r->id; ?>" data-group-id="<?php echo (int) $r->location_group_id; ?>"> <?php echo htmlspecialchars($r->name, ENT_QUOTES, 'UTF-8'); ?> </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="new_room_btn" class="ny-btn">Ny</button>
            </label>
            <div id="new_room_div" style="display:none; margin-top: 5px;">
                <input type="text" name="room_name" placeholder="Nytt rum..." required>
                <input type="hidden" name="room_group_id" id="new_room_group_id">
                <input type="hidden" name="mod_task" value="create.room">
                <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                <button type="submit" id="create_room_btn">Skapa rum</button>
                <button type="button" id="cancel_room_btn">Avbryt</button>
            </div>
        </form>
        <!-- Skrivbord -->
        <form method="post" id="createDeskForm">
            <input type="hidden" name="mod_task" value="create.desk">
            <input type="hidden" name="desk_room_id" id="desk_room_id">
            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
            <button type="submit" id="create_desk_btn" disabled>Skapa skrivbord</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Visa/dölj inputfält för nya objekt.
        // Plats
        const newLocationBtn = document.getElementById('new_location_btn');
        const newLocationDiv = document.getElementById('new_location_div');
        const cancelLocationBtn = document.getElementById('cancel_location_btn');
        newLocationBtn.onclick = function() {
            newLocationDiv.style.display = 'block';
            newLocationBtn.style.display = 'none';
        };
        cancelLocationBtn.onclick = function() {
            newLocationDiv.style.display = 'none';
            newLocationBtn.style.display = '';
        };

        // Avdelning
        const newGroupBtn = document.getElementById('new_group_btn');
        const newGroupDiv = document.getElementById('new_group_div');
        const cancelGroupBtn = document.getElementById('cancel_group_btn');
        newGroupBtn.onclick = function() {
            newGroupDiv.style.display = 'block';
            newGroupBtn.style.display = 'none';
            document.getElementById('new_group_location_id').value = document.getElementById('location_select').value;
        };
        cancelGroupBtn.onclick = function() {
            newGroupDiv.style.display = 'none';
            newGroupBtn.style.display = '';
        };

        // Rum
        const newRoomBtn = document.getElementById('new_room_btn');
        const newRoomDiv = document.getElementById('new_room_div');
        const cancelRoomBtn = document.getElementById('cancel_room_btn');
        newRoomBtn.onclick = function() {
            newRoomDiv.style.display = 'block';
            newRoomBtn.style.display = 'none';
            document.getElementById('new_room_group_id').value = document.getElementById('group_select').value;
        };
        cancelRoomBtn.onclick = function() {
            newRoomDiv.style.display = 'none';
            newRoomBtn.style.display = '';
        };

        // Filtrering och aktivering av skapa-knappar.
        // Avdelning: filtrera avdelningar baserat på vald plats
        const groupSelect = document.getElementById('group_select');
        const groupOptions = groupSelect.querySelectorAll('option[data-location-id]');
        const locationSelect = document.getElementById('location_select');
        locationSelect.addEventListener('change', function() {
            const selectedLocationId = this.value;
            groupSelect.value = '';
            groupOptions.forEach(function(option) {
                option.style.display = 'none';
                if (option.getAttribute('data-location-id') === selectedLocationId) {
                    option.style.display = '';
                }
            });
        });

        // Rum: filtrera rum baserat på vald avdelning.
        const roomSelect = document.getElementById('room_select');
        const roomOptions = roomSelect.querySelectorAll('option[data-group-id]');
        groupSelect.addEventListener('change', function() {
            const selectedGroupId = this.value;
            roomSelect.value = '';
            roomOptions.forEach(function(option) {
                option.style.display = 'none';
                if (option.getAttribute('data-group-id') === selectedGroupId) {
                    option.style.display = '';
                }
            });
        });

        // Skrivbord: filtrera avdelningar och rum, aktivera skapa-knapp om rum är vald.
        const deskLocationSelect = document.getElementById('location_select');
        const deskGroupSelect = document.getElementById('group_select');
        const deskRoomSelect = document.getElementById('room_select');
        const createDeskBtn = document.getElementById('create_desk_btn');
        deskRoomSelect.addEventListener('change', function() {
            createDeskBtn.disabled = !deskRoomSelect.value;
            document.getElementById('desk_room_id').value = deskRoomSelect.value;
        });
        createDeskBtn.disabled = true;
        document.getElementById('desk_room_id').value = deskRoomSelect.value;
    });
</script>