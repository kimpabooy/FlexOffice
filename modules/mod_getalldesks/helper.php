<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ModGetAllDesksHelper
{
    public static function getDesks()
    {
        // Hämta databasobjekt via DI eller fallback
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        $query = $db->getQuery(true)
            // Hämta desk id, rumsnamn och räkna tillgängliga perioder (availability_count)
            ->select([
                $db->quoteName('d.id'),
                $db->quoteName('r.name') . ' AS ' . $db->quoteName('room_name'),
            ])
            ->from($db->quoteName('#__desk', 'd'))
            ->join('LEFT', $db->quoteName('#__rooms', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('d.room_id'))
            ->order($db->quoteName('d.id'));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (Exception $e) {
            // Logga felet om Log-klassen finns
            if (class_exists(\Joomla\CMS\Log\Log::class)) {
                \Joomla\CMS\Log\Log::add($e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_getAllDesks');
            }
            return [];
        }
    }
}
