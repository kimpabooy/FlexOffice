<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

class PersonalDeskService
{
    public static function getDbInstance()
    {
        if (method_exists(Factory::class, 'getContainer')) {
            return Factory::getContainer()->get(DatabaseInterface::class);
        }

        return Factory::getDbo();
    }

    public static function getTableColumns($db, $tableName)
    {
        try {
            $db->setQuery('DESCRIBE ' . $db->quoteName($db->getPrefix() . $tableName));
            return $db->loadColumn();
        } catch (\RuntimeException $e) {
            Log::add('Failed to DESCRIBE ' . $tableName . ': ' . $e->getMessage(), Log::DEBUG, 'mod_personal_desk_availability');
            return [];
        }
    }

    public static function getCurrentUserId()
    {
        $user = Factory::getApplication()->getIdentity();
        return isset($user->id) && $user->id ? (int) $user->id : null;
    }

    public static function buildInsertColumns($db, array $tableCols)
    {
        $insertCols = [];
        if (in_array('desk_id', $tableCols)) {
            $insertCols[] = $db->quoteName('desk_id');
        }
        if (in_array('name', $tableCols)) {
            $insertCols[] = $db->quoteName('name');
        }
        if (in_array('start_time', $tableCols)) {
            $insertCols[] = $db->quoteName('start_time');
        }
        if (in_array('end_time', $tableCols)) {
            $insertCols[] = $db->quoteName('end_time');
        }
        if (in_array('created_by', $tableCols)) {
            $insertCols[] = $db->quoteName('created_by');
        }
        if (in_array('created_at', $tableCols)) {
            $insertCols[] = $db->quoteName('created_at');
        }

        return $insertCols;
    }

    public static function createOccurrences($db, array $insertCols, array $tableCols, $deskId, $label, \DateTime $first, $repeatCount, $durationSeconds, $userId)
    {
        $insertedIds = [];

        for ($i = 0; $i < $repeatCount; $i++) {
            $occStart = clone $first;
            if ($i > 0) {
                $occStart->modify('+' . $i . ' week');
            }
            $occStartStr = $occStart->format('Y-m-d H:i:s');

            $occEndStr = null;
            if ($durationSeconds !== null) {
                $occEnd = clone $occStart;
                $occEnd->modify('+' . $durationSeconds . ' seconds');
                $occEndStr = $occEnd->format('Y-m-d H:i:s');
            }

            $vals = [];
            if (in_array('desk_id', $tableCols)) {
                $vals[] = (int) $deskId;
            }
            if (in_array('name', $tableCols)) {
                $vals[] = $db->quote($label);
            }
            if (in_array('start_time', $tableCols)) {
                $vals[] = $db->quote($occStartStr);
            }
            if (in_array('end_time', $tableCols)) {
                $vals[] = $occEndStr !== null ? $db->quote($occEndStr) : 'NULL';
            }
            if (in_array('created_by', $tableCols)) {
                $vals[] = $userId !== null ? (int) $userId : 'NULL';
            }
            if (in_array('created_at', $tableCols)) {
                $vals[] = $db->quote(date('Y-m-d H:i:s'));
            }

            $q = $db->getQuery(true)
                ->insert($db->quoteName('#__fbgflexoffice_desk_availability_period'))
                ->columns($insertCols)
                ->values(implode(',', $vals));
            $db->setQuery($q);
            $db->execute();
            $insertedIds[] = (int) $db->insertid();
        }

        return $insertedIds;
    }

    public static function runCleanupIfNeeded($db)
    {
        $deskCols = self::getTableColumns($db, 'fbgflexoffice_desk');
        if (!in_array('desk_availability_period_id', $deskCols)) {
            return;
        }

        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $cleanup = $db->getQuery(true)
                ->update($db->quoteName('#__fbgflexoffice_desk'))
                ->set($db->quoteName('desk_availability_period_id') . ' = NULL')
                ->where($db->quoteName('desk_availability_period_id') . ' IN (SELECT ' . $db->quoteName('id') . ' FROM ' . $db->quoteName('#__fbgflexoffice_desk_availability_period') . ' WHERE ' . $db->quoteName('end_time') . ' IS NOT NULL AND ' . $db->quoteName('end_time') . ' < ' . $db->quote($now) . ')');
            $db->setQuery($cleanup);
            $db->execute();
        } catch (\RuntimeException $e) {
            Log::add('Cleanup failed for #__fbgflexoffice_desk: ' . $e->getMessage(), Log::ERROR, 'mod_personal_desk_availability');
        }
    }

    /**
     * Return list of desks (optionally limited to current user's desks inside the query logic).
     *
     * @return array
     */
    public static function getDesks()
    {
        $db = self::getDbInstance();

        // Run cleanup if needed
        self::runCleanupIfNeeded($db);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('d.id'),
                $db->quoteName('r.name') . ' AS ' . $db->quoteName('room_name'),
            ])
            ->from($db->quoteName('#__fbgflexoffice_desk', 'd'))
            ->join('LEFT', $db->quoteName('#__fbgflexoffice_room', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('d.room_id'))
            ->order($db->quoteName('d.id'));

        // Only show desks that belong to the currently logged-in user
        $userId = self::getCurrentUserId();
        if ($userId !== null && $userId > 0) {
            $query->where($db->quoteName('d.user_id') . ' = ' . $userId);
        }

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\RuntimeException $e) {
            Log::add('Failed to load desks: ' . $e->getMessage(), Log::ERROR, 'mod_personal_desk_availability');
            return [];
        }
    }

    /**
     * Return recent availability periods.
     *
     * @return array
     */
    public static function getRecentAvails($isSuperUser = false)
    {
        $db = self::getDbInstance();

        $tableCols = self::getTableColumns($db, 'fbgflexoffice_desk_availability_period');
        if (empty($tableCols)) {
            return [];
        }

        $selectCols = [];
        foreach (['id', 'desk_id', 'name', 'start_time', 'end_time', 'created_at', 'created_by'] as $col) {
            if (in_array($col, $tableCols)) {
                $selectCols[] = 'p.' . $db->quoteName($col);
            }
        }

        $query = $db->getQuery(true)
            ->select($selectCols)
            ->from($db->quoteName('#__fbgflexoffice_desk_availability_period', 'p'));

        $deskJoinUsed = false;
        $deskCols = self::getTableColumns($db, 'fbgflexoffice_desk');

        if (in_array('desk_id', $tableCols)) {
            $query->join('LEFT', $db->quoteName('#__fbgflexoffice_desk', 'd') . ' ON d.id = p.desk_id');
            $deskJoinUsed = true;
        } elseif (in_array('desk_availability_period_id', $deskCols)) {
            $query->join('LEFT', $db->quoteName('#__fbgflexoffice_desk', 'd') . ' ON d.desk_availability_period_id = p.id');
            $deskJoinUsed = true;
        }

        if ($deskJoinUsed) {
            $query->join('LEFT', $db->quoteName('#__fbgflexoffice_room', 'r') . ' ON r.id = d.room_id');
            $query->select($db->quoteName('r.name') . ' AS ' . $db->quoteName('room_name'));
            if (!in_array('p.' . $db->quoteName('desk_id'), $selectCols)) {
                $query->select('d.' . $db->quoteName('id') . ' AS ' . $db->quoteName('desk_id'));
            }
        }

        if (in_array('created_by', $tableCols)) {
            $query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . 'u.id = p.created_by');
            $query->select($db->quoteName('u.name') . ' AS ' . $db->quoteName('created_by_name'));
        }

        // Filter by current user unless super user
        if (!$isSuperUser) {
            $userId = self::getCurrentUserId();
            if ($userId !== null && $userId > 0 && in_array('created_by', $tableCols)) {
                $query->where('p.' . $db->quoteName('created_by') . ' = ' . (int) $userId);
            }
        }

        $query->order('p.' . $db->quoteName('start_time') . ' DESC');

        try {
            return $db->loadObjectList();
        } catch (\RuntimeException $e) {
            Log::add('Failed to load recent avails: ' . $e->getMessage(), Log::ERROR, 'mod_personal_desk_availability');
            return [];
        }
    }


    public static function handleRequest($ajax = false)
    {
        $app = Factory::getApplication();
        $input = $app->input;

        // Only handle POST requests with the correct task
        if ($input->getMethod() !== 'POST' || $input->getCmd('task') !== 'personaldesk.save') {
            if ($ajax) {
                return ['success' => false, 'message' => 'Felaktig metod eller task'];
            }
            return;
        }

        if (!Session::checkToken('post')) {
            if ($ajax) {
                return ['success' => false, 'message' => 'Felaktig token.'];
            }
            $app->enqueueMessage('Felaktig token.', 'error');
            return;
        }

        $deskId = $input->getInt('desk_id');
        $label = $input->getString('label');
        $startDate = $input->getString('start_date');
        $startTime = $input->getString('start_time');
        $endDate = $input->getString('end_date');
        $endTime = $input->getString('end_time');

        // Determine selected mode early so validation can depend on it
        $mode = $input->getCmd('availability_mode', 'range');
        $repeatCount = max(1, $input->getInt('repeat_count', 1));
        $repeatWeekday = (int) $input->getInt('repeat_weekday', 0);

        // Basic validation depending on mode
        if (!$deskId) {
            if ($ajax) {
                return ['success' => false, 'message' => 'Ange skrivbord.'];
            }
            $app->enqueueMessage('Ange skrivbord.', 'warning');
            return;
        }

        if ($mode === 'range') {
            if (!$startDate || !$startTime) {
                if ($ajax) {
                    return ['success' => false, 'message' => 'Ange startdatum och starttid för intervallet.'];
                }
                $app->enqueueMessage('Ange startdatum och starttid för intervallet.', 'warning');
                return;
            }
        } else {
            // weekday mode: require at least a reference start date and a valid weekday selection
            if (!$startDate) {
                if ($ajax) {
                    return ['success' => false, 'message' => 'Ange referensdatum för upprepningen.'];
                }
                $app->enqueueMessage('Ange referensdatum för upprepningen.', 'warning');
                return;
            }
            if ($repeatWeekday < 1 || $repeatWeekday > 7) {
                if ($ajax) {
                    return ['success' => false, 'message' => 'Vänligen välj en giltig veckodag för upprepning.'];
                }
                $app->enqueueMessage('Vänligen välj en giltig veckodag för upprepning.', 'warning');
                return;
            }
        }

        // Combine date and time (if start_time missing we use 00:00 which will be adjusted for weekday mode)
        $start = date('Y-m-d H:i:s', strtotime($startDate . ' ' . ($startTime ?: '00:00:00')));
        $end = null;
        if (!empty($endDate) && !empty($endTime)) {
            $end = date('Y-m-d H:i:s', strtotime($endDate . ' ' . $endTime));
        }

        // DB
        $db = self::getDbInstance();

        $userId = self::getCurrentUserId();

        // Hämta vilka kolumner som faktiskt finns i availability-tabellen
        $tableCols = self::getTableColumns($db, 'fbgflexoffice_desk_availability_period');
        if (empty($tableCols)) {
            if ($ajax) {
                return ['success' => false, 'message' => 'Databas-tabellen för availability kunde inte läsas eller saknar kolumner.'];
            }
            $app->enqueueMessage('Databas-tabellen för availability kunde inte läsas eller saknar kolumner.', 'error');
            return;
        }

        $insertCols = self::buildInsertColumns($db, $tableCols);
        if (empty($insertCols)) {
            if ($ajax) {
                return ['success' => false, 'message' => 'Inga kända kolumner att skriva till i availability-tabellen.'];
            }
            $app->enqueueMessage('Inga kända kolumner att skriva till i availability-tabellen.', 'error');
            return;
        }

        // Kontrollera att vi åtminstone har start_time eller name
        if (!in_array('start_time', $tableCols) && !in_array('name', $tableCols)) {
            if ($ajax) {
                return ['success' => false, 'message' => 'Tabellen saknar både start_time och name - inget att spara.'];
            }
            $app->enqueueMessage('Tabellen saknar både start_time och name - inget att spara.', 'error');
            return;
        }

        try {
            $startDt = new \DateTime($start);
            $durationSeconds = null;

            if ($mode === 'weekday') {
                // Weekday mode: full work day 08:00 - 17:00
                if ($repeatWeekday < 1 || $repeatWeekday > 7) {
                    if ($ajax) {
                        return ['success' => false, 'message' => 'Vänligen välj en giltig veckodag för upprepning.'];
                    }
                    $app->enqueueMessage('Vänligen välj en giltig veckodag för upprepning.', 'warning');
                    return;
                }
                // Force start time to 08:00 on the provided start date (reference)
                $startDt->setTime(8, 0, 0);
                // duration 9 hours
                $durationSeconds = 9 * 3600;
            } else {
                // Range mode: use supplied end date/time if provided
                if ($end !== null) {
                    $endDt = new \DateTime($end);
                    $durationSeconds = $endDt->getTimestamp() - $startDt->getTimestamp();
                }
                // For range mode, don't repeat
                $repeatCount = 1;
            }

            // Inspect desk table columns to determine whether we can set desk_availability_period_id
            $deskCols = self::getTableColumns($db, 'fbgflexoffice_desk');

            // Determine the first occurrence. For weekday mode: find the first date on/after
            // the provided start date that matches that weekday (1=Mon..7=Sun). Otherwise use start date.
            if ($mode === 'weekday') {
                $first = clone $startDt;
                $currentN = (int) $first->format('N'); // 1..7
                $delta = ($repeatWeekday - $currentN + 7) % 7;
                if ($delta > 0) {
                    $first->modify('+' . $delta . ' days');
                }
                // Ensure first occurrence still set to 08:00
                $first->setTime(8, 0, 0);
            } else {
                $first = clone $startDt;
            }

            $insertedIds = self::createOccurrences($db, $insertCols, $tableCols, $deskId, $label, $first, $repeatCount, $durationSeconds, $userId);

            // If desk_availability_period_id exists in desks table, update it to the first inserted period id
            if (in_array('desk_availability_period_id', $deskCols) && !empty($insertedIds)) {
                try {
                    $firstId = (int) $insertedIds[0];
                    $update = $db->getQuery(true)
                        ->update($db->quoteName('#__fbgflexoffice_desk'))
                        ->set($db->quoteName('desk_availability_period_id') . ' = ' . $db->quote($firstId))
                        ->where($db->quoteName('id') . ' = ' . (int) $deskId);
                    $db->setQuery($update);
                    $db->execute();
                } catch (\RuntimeException $e) {
                    if ($ajax) {
                        return ['success' => true, 'message' => 'Period(er) sparad, men kunde inte uppdatera skrivbordet: ' . $e->getMessage()];
                    }
                    $app->enqueueMessage('Sparat period(er), men kunde inte uppdatera skrivbordet: ' . $e->getMessage(), 'warning');
                }
            }

            if ($ajax) {
                return ['success' => true, 'message' => 'Period(er) sparad.'];
            }
            $app->enqueueMessage('Period(er) sparad.', 'message');
            $redirectUrl = $input->server->getString('REQUEST_URI');
            // Use a direct header redirect since the application object may not implement redirect()
            header('Location: ' . $redirectUrl);
            exit;
        } catch (\RuntimeException $e) {
            if ($ajax) {
                return ['success' => false, 'message' => 'Kunde inte spara period(er): ' . $e->getMessage()];
            }
            $app->enqueueMessage('Kunde inte spara period(er): ' . $e->getMessage(), 'error');
        }
    }
}
