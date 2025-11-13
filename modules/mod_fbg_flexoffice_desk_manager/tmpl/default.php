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
        <label>Avdelning<br>
            <select name="room_group_id" required>
                <option value="">-- Välj --</option>
                <?php foreach ($groups as $g) : ?>
                    <option value="<?php echo (int) $g->id; ?>"><?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?></option>
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
        <label>Avdelning<br>
            <select id="desk_group_select">
                <option value="">-- Välj avdelning --</option>
                <?php foreach ($groups as $g) : ?>
                    <option value="<?php echo (int) $g->id; ?>"><?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?></option>
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
        const groupSelect = document.getElementById('desk_group_select');
        const roomSelect = document.getElementById('desk_room_select');
        const roomOptions = roomSelect.querySelectorAll('option[data-group-id]');

        groupSelect.addEventListener('change', function() {
            const selectedGroupId = this.value;

            // Reset room select
            roomSelect.value = '';

            // Hide all options
            roomOptions.forEach(function(option) {
                option.style.display = 'none';
            });

            if (selectedGroupId) {
                // Show options that match the selected group
                const filteredOptions = roomSelect.querySelectorAll('option[data-group-id="' + selectedGroupId + '"]');
                filteredOptions.forEach(function(option) {
                    option.style.display = '';
                });
            }
        });
    });
</script>