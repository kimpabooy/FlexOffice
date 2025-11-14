<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ModFbgFlexofficeGetAllBookingsHelper
{
    public static function getBookings()
    {
        // Hämta databasobjekt via DI eller fallback
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        // Aliasera booking-tabellen som 'b' och JOIN mot users för att få användarens namn
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('b.id'),
                $db->quoteName('b.desk_id'),
                $db->quoteName('b.user_id'),
                $db->quoteName('b.start_time'),
                $db->quoteName('b.end_time'),
                $db->quoteName('u.name') . ' AS ' . $db->quoteName('user_name'),
            ])
            ->from($db->quoteName('#__fbgflexoffice_booking', 'b'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('b.user_id'));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (Exception $e) {
            // Logga felet om Log-klassen finns
            if (class_exists(\Joomla\CMS\Log\Log::class)) {
                \Joomla\CMS\Log\Log::add($e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_getAllBookings');
            }
            return [];
        }
    }
}
