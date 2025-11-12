<?php
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;

?>
<div class="mod-managestatus">
    <h3>Statusperioder för desks</h3>

    <?php if (empty($statusPeriods)) : ?>
        <p>Inga statusperioder funna.</p>
    <?php else : ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Desk</th>
                    <th>Status</th>
                    <th>Från</th>
                    <th>Till</th>
                    <th>Skapad av</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($statusPeriods as $sp) : ?>
                <tr>
                    <td><?php echo (int) $sp->id; ?></td>
                    <td><?php echo (int) $sp->desk_id; ?></td>
                    <td><?php echo htmlspecialchars($sp->status_id ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($sp->start_time ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($sp->end_time ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($sp->created_by_name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h4>Lägg till statusperiod</h4>
    <form method="post">
        
        <div>
            <label for="desk_id">Desk</label>
            <select name="desk_id" id="desk_id">
                <?php foreach ($desks as $d) : ?>
                    <option value="<?php echo (int) $d->id; ?>"><?php echo 'Skrivbord # ' . (int) $d->id; ?><?php echo isset($d->room_name) ? ' —  Rum: ' . htmlspecialchars($d->room_name, ENT_QUOTES, 'UTF-8') : ' '; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="status_id">Status</label>
            <select name="status_id" id="status_id">
                <?php foreach ($statusTypes as $s) : ?>
                    <option value="<?php echo (int) $s->id; ?>"><?php echo htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="start_time">Från (YYYY-MM-DD HH:MM)</label>
            <input type="text" name="start_time" id="start_time" placeholder="Enter Start Time">
        </div>
        
        <div>
            <label for="end_time">Till (YYYY-MM-DD HH:MM)</label>
            <input type="text" name="end_time" id="end_time" placeholder="Enter End Time">
        </div>

        <div>            
            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1" />
        </div>

        <button type="submit">Spara period</button>
    </form>
</div>
