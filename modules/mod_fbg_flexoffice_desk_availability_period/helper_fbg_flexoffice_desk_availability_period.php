<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ModFbgFlexofficeDeskAvailabilityPeriodHelper
{
    public static function getAvailabilityPeriods($limit = 20)
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }
        // Adapt to schema: include desk information when possible. Some installs
        // link desks from periods (p.desk_id) while others have desks reference
        // a period (d.desk_availability_period_id = p.id). We try to detect and
        // join accordingly. We also aggregate desk ids in case multiple desks map
        // to the same period.
        try {
            $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : '';
            $tableReal = $prefix . 'fbgflexoffice_desk_availability_period';
            $db->setQuery('DESCRIBE ' . $db->quoteName($tableReal));
            $tableCols = $db->loadColumn();
        } catch (\RuntimeException $e) {
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
            ->from($db->quoteName('#__fbgflexoffice_desk_availability_period', 'p'))
            // Exclude placeholder rows where both start_time and end_time are NULL
            ->where('(' . $db->quoteName('p.start_time') . ' IS NOT NULL OR ' . $db->quoteName('p.end_time') . ' IS NOT NULL)');

        // Cleanup: unset fbgflexoffice_desk_availability_period_id for desks whose period has ended
        // (Some installs rely on fbgflexoffice_desks.desk_availability_period_id so keep data consistent.)
        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $cleanup = $db->getQuery(true)
                ->update($db->quoteName('#__fbgflexoffice_desk'))
                ->set($db->quoteName('desk_availability_period_id') . ' = NULL')
                ->where($db->quoteName('desk_availability_period_id') . ' IN (SELECT ' . $db->quoteName('id') . ' FROM ' . $db->quoteName('#__fbgflexoffice_desk_availability_period') . ' WHERE ' . $db->quoteName('end_time') . ' IS NOT NULL AND ' . $db->quoteName('end_time') . ' < ' . $db->quote($now) . ')');
            $db->setQuery($cleanup);
            $db->execute();
        } catch (\RuntimeException $e) {
            // Non-fatal: ignore cleanup failures to avoid breaking listing
        }

        // Inspect desks table to determine linking strategy
        try {
            $db->setQuery('DESCRIBE ' . $db->quoteName($db->getPrefix() . 'fbgflexoffice_desk'));
            $deskCols = $db->loadColumn();
        } catch (\RuntimeException $e) {
            $deskCols = [];
        }

        $deskJoinUsed = false;
        if (in_array('desk_id', $tableCols)) {
            // periods reference desks directly
            $query->join('LEFT', $db->quoteName('#__fbgflexoffice_desk', 'd') . ' ON d.id = p.desk_id');
            $deskJoinUsed = true;
        } elseif (in_array('desk_availability_period_id', $deskCols)) {
            // desks reference periods
            $query->join('LEFT', $db->quoteName('#__fbgflexoffice_desk', 'd') . ' ON d.desk_availability_period_id = p.id');
            $deskJoinUsed = true;
        }

        if ($deskJoinUsed) {
            // Join rooms and select aggregated desk info
            $query->join('LEFT', $db->quoteName('#__fbgflexoffice_room', 'r') . ' ON r.id = d.room_id');
            $query->select($db->quoteName('r.name') . ' AS ' . $db->quoteName('room_name'));
            // Deterministic single desk id (use MIN) and list of desk ids
            $query->select('MIN(d.' . $db->quoteName('id') . ') AS ' . $db->quoteName('desk_id'));
            $query->select('GROUP_CONCAT(DISTINCT d.' . $db->quoteName('id') . ' ORDER BY d.' . $db->quoteName('id') . ' SEPARATOR ",") AS ' . $db->quoteName('desk_ids'));
            // When selecting aggregated columns, ensure we group by period id
            $query->group('p.' . $db->quoteName('id'));
        }

        // Exclude expired periods from the result set when end_time exists
        if (in_array('end_time', $tableCols)) {
            // Use the same $now value as the cleanup above (if available), else compute
            if (!isset($now)) {
                $now = (new \DateTime())->format('Y-m-d H:i:s');
            }
            $query->where('p.' . $db->quoteName('end_time') . ' IS NULL OR p.' . $db->quoteName('end_time') . ' >= ' . $db->quote($now));
        }

        if (in_array('created_by', $tableCols)) {
            $query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = p.created_by');
            $query->select($db->quoteName('u.name') . ' AS ' . $db->quoteName('created_by_name'));
        }

        $query->order('p.' . $db->quoteName('start_time') . ' ASC');

        // Apply limit if provided (caller passes module parameter)
        if ($limit && is_int($limit)) {
            $db->setQuery($query, 0, $limit);
        } else {
            $db->setQuery($query);
        }

        try {
            return $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    public static function getDeskId()
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        $query = $db->getQuery(true)
            ->select([
                // Explicitly qualify id to avoid ambiguity when joining
                'd.' . $db->quoteName('id') . ' AS ' . $db->quoteName('id'),
                // Select room name as room_name
                $db->quoteName('r.name') . ' AS ' . $db->quoteName('room_name'),
            ])
            ->from($db->quoteName('#__fbgflexoffice_desk', 'd'))
            ->join('LEFT', $db->quoteName('#__fbgflexoffice_room', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('d.room_id'))
            ->order($db->quoteName('d.id'));
        $db->setQuery($query);
        // Return list of desks (loadObjectList) — caller can pick what it needs
        return $db->loadObjectList();
    }
}
