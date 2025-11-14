<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ModFbgFlexofficeGetAllRoomsHelper
{
    public static function getRooms()
    {
        // $db = Factory::getDbo(); // Deprecated method
        // Prefer the DI container database service; fall back to Factory::getDbo() for older Joomla versions.
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__fbgflexoffice_room'));
        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (Exception $e) {
            return [];
        }
    }
}
