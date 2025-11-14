<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ModFbgFlexofficeBookingHelper
{
    /**
     * Return availability periods from #__desk_availability_period that are not
     * already booked (no overlapping rows in #__booking).
     *
     * @return array
     */
    public static function getAvailable()
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $query = $db->getQuery(true)
            ->select([
                'p.*',
                $db->quoteName('r.name') . ' AS ' . $db->quoteName('room_name'),
                $db->quoteName('lg.name') . ' AS ' . $db->quoteName('location_group_name'),
                $db->quoteName('l.name') . ' AS ' . $db->quoteName('location_name')
            ])
            ->from($db->quoteName('#__fbgflexoffice_desk_availability_period', 'p'))
            ->join('LEFT', $db->quoteName('#__fbgflexoffice_desk', 'd') . ' ON d.id = p.desk_id')
            ->join('LEFT', $db->quoteName('#__fbgflexoffice_room', 'r') . ' ON r.id = d.room_id')
            ->join('LEFT', $db->quoteName('#__fbgflexoffice_location_group', 'lg') . ' ON lg.id = r.location_group_id')
            ->join('LEFT', $db->quoteName('#__fbgflexoffice_location', 'l') . ' ON l.id = lg.location_id')
            // Exclude placeholder rows where both start_time and end_time are NULL
            ->where('(' . $db->quoteName('p.start_time') . ' IS NOT NULL OR ' . $db->quoteName('p.end_time') . ' IS NOT NULL)')
            ->where('(p.' . $db->quoteName('end_time') . ' IS NULL OR p.' . $db->quoteName('end_time') . ' >= ' . $db->quote($now) . ')');

        // Exclude already booked periods (overlapping bookings for same desk)
        $sub = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__fbgflexoffice_booking', 'b'))
            ->where('b.' . $db->quoteName('desk_id') . ' = p.' . $db->quoteName('desk_id'))
            ->where('(' .
                'b.' . $db->quoteName('start_time') . ' < COALESCE(p.' . $db->quoteName('end_time') . ', ' . $db->quote('9999-12-31 23:59:59') . ')' .
                ' AND b.' . $db->quoteName('end_time') . ' > p.' . $db->quoteName('start_time') .
                ')');

        $query->where('NOT EXISTS (' . $sub . ')');
        $query->order('p.' . $db->quoteName('start_time') . ' ASC');

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    /**
     * Get bookings (used for display in the prototype). Returns booking objects.
     *
     * @return array
     */
    public static function getBookings($isSuperUser = false)
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = method_exists(Factory::class, 'getContainer') ? Factory::getContainer()->get(DatabaseInterface::class) : Factory::getDbo();
        }
        // Show only active (non-expired) bookings: those without an end_time or with end_time >= now
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        // Select bookings and join to users to fetch display name for nicer output
        $query = $db->getQuery(true)
            ->select(['b.*', $db->quoteName('u.name') . ' AS ' . $db->quoteName('user_name')])
            ->from($db->quoteName('#__fbgflexoffice_booking', 'b'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = b.user_id')
            ->where('(b.' . $db->quoteName('end_time') . ' IS NULL OR b.' . $db->quoteName('end_time') . ' >= ' . $db->quote($now) . ')')
            ->order('b.start_time ASC');

        if (!$isSuperUser) {
            $user = \Joomla\CMS\Factory::getApplication()->getIdentity();
            if ($user->id) {
                $query->where($db->quoteName('b.user_id') . ' = ' . (int) $user->id);
            } else {
                return [];
            }
        }

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    /**
     * Save booking into #__booking. Requires keys: desk_id, user_id, start_time, end_time
     *
     * @param array $booking
     * @return bool
     */
    public static function saveBooking(array $booking)
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        if (empty($booking['desk_id']) || empty($booking['user_id']) || empty($booking['start_time'])) {
            return false;
        }

        $endTime = isset($booking['end_time']) && $booking['end_time'] ? $booking['end_time'] : null;

        // Check conflicts
        $subQ = $db->getQuery(true)
            ->select('COUNT(1)')
            ->from($db->quoteName('#__fbgflexoffice_booking', 'b'))
            ->where('b.' . $db->quoteName('desk_id') . ' = ' . (int) $booking['desk_id'])
            ->where('(' .
                'b.' . $db->quoteName('start_time') . ' < ' . $db->quote($endTime ?: '9999-12-31 23:59:59') .
                ' AND b.' . $db->quoteName('end_time') . ' > ' . $db->quote($booking['start_time']) .
                ')');

        $db->setQuery($subQ);
        try {
            $count = (int) $db->loadResult();
        } catch (\RuntimeException $e) {
            return false;
        }

        if ($count > 0) {
            return false;
        }

        $columns = ['desk_id', 'user_id', 'start_time', 'end_time'];
        $values = [(int) $booking['desk_id'], (int) $booking['user_id'], $db->quote($booking['start_time']), $endTime ? $db->quote($endTime) : 'NULL'];

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__fbgflexoffice_booking'))
            ->columns(array_map([$db, 'quoteName'], $columns))
            ->values(implode(',', $values));

        $db->setQuery($query);
        try {
            $db->execute();
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Get personal bookings for the current user.
     *
     * @return array
     */
    public static function getPersonalBookings()
    {
        $user = \Joomla\CMS\Factory::getApplication()->getIdentity();
        if (!$user->id) {
            return [];
        }

        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $query = $db->getQuery(true)
            ->select(['b.*', $db->quoteName('u.name') . ' AS ' . $db->quoteName('user_name')])
            ->from($db->quoteName('#__fbgflexoffice_booking', 'b'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = b.user_id')
            ->where('b.user_id = ' . (int) $user->id)
            ->where('(b.' . $db->quoteName('end_time') . ' IS NULL OR b.' . $db->quoteName('end_time') . ' >= ' . $db->quote($now) . ')')
            ->order('b.start_time ASC');

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    /**
     * Cancel a booking.
     *
     * @param int $bookingId
     * @param int $userId
     * @return bool
     */
    public static function cancelBooking(int $bookingId, int $userId, bool $isSuperUser = false)
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__fbgflexoffice_booking'))
            ->where($db->quoteName('id') . ' = ' . $bookingId);

        if (!$isSuperUser) {
            $query->where($db->quoteName('user_id') . ' = ' . $userId);
        }

        $db->setQuery($query);

        try {
            $db->execute();
            return $db->getAffectedRows() > 0;
        } catch (\RuntimeException $e) {
            return false;
        }
    }
}
