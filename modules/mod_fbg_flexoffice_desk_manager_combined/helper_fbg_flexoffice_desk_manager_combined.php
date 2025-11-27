<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

class ModFbgFlexofficeDeskManagerCombinedHelper
{
    public static function process()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        if ($input->getMethod() !== 'POST') {
            return;
        }

        if (!Session::checkToken()) {
            $app->enqueueMessage('Invalid token', 'error');
            return;
        }

        $db = method_exists(Factory::class, 'getContainer') ? Factory::getContainer()->get(DatabaseInterface::class) : Factory::getDbo();
        $task = $input->post->getString('mod_task');

        try {
            switch ($task) {
                case 'create.location':
                    $name = trim($input->post->getString('location_name'));
                    if ($name === '') {
                        throw new \RuntimeException('Location name is required');
                    }
                    $columns = ['name'];
                    $values = [$db->quote($name)];
                    self::insertWithIdFallback('l5e0b_fbgflexoffice_location', $columns, $values);
                    $app->enqueueMessage('Location created', 'message');
                    break;
                case 'create.group':
                    $location_id = (int) $input->post->getInt('group_location_id');
                    $name = trim($input->post->getString('group_name'));
                    if ($location_id <= 0 || $name === '') {
                        throw new \RuntimeException('Location and Group name are required');
                    }
                    $columns = ['location_id', 'name'];
                    $values = [(int) $location_id, $db->quote($name)];
                    self::insertWithIdFallback('l5e0b_fbgflexoffice_location_group', $columns, $values);
                    $app->enqueueMessage('Group created', 'message');
                    break;
                case 'create.room':
                    $group_id = (int) $input->post->getInt('room_group_id');
                    $name = trim($input->post->getString('room_name'));
                    if ($group_id <= 0 || $name === '') {
                        throw new \RuntimeException('Group and Room name are required');
                    }
                    $columns = ['location_group_id', 'name'];
                    $values = [(int) $group_id, $db->quote($name)];
                    self::insertWithIdFallback('l5e0b_fbgflexoffice_room', $columns, $values);
                    $app->enqueueMessage('Room created', 'message');
                    break;
                case 'create.desk':
                    $room_id = (int) $input->post->getInt('desk_room_id');
                    $name = trim($input->post->getString('desk_name'));
                    if ($room_id <= 0) {
                        throw new \RuntimeException('Room is required');
                    }
                    $user = Factory::getUser();
                    $userId = isset($user->id) ? (int) $user->id : 0;
                    $availabilityId = 1;
                    $columns = ['room_id', 'desk_availability_period_id', 'user_id'];
                    $values = [(int) $room_id, (int) $availabilityId, (int) $userId];
                    self::insertWithIdFallback('l5e0b_fbgflexoffice_desk', $columns, $values);
                    $app->enqueueMessage('Desk created', 'message');
                    break;
                default:
                    $app->enqueueMessage('Unknown task', 'warning');
                    break;
            }
        } catch (\Exception $e) {
            $app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }

        $uri = $app->input->server->getString('REQUEST_URI', '');
        header('Location: ' . $uri);
        exit;
    }

    private static function insertWithIdFallback($table, array $columns, array $values)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->insert($db->quoteName($table))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
        $db->setQuery($query);
        try {
            $db->execute();
            return;
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "Field 'id' doesn't have a default value") !== false) {
                $db->setQuery('SELECT MAX(' . $db->quoteName('id') . ') FROM ' . $db->quoteName($table));
                $max = (int) $db->loadResult();
                $newId = $max + 1;
                array_unshift($columns, 'id');
                array_unshift($values, (int) $newId);
                $query = $db->getQuery(true)
                    ->insert($db->quoteName($table))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));
                $db->setQuery($query);
                $db->execute();
                return;
            }
            throw $e;
        }
    }

    public static function getLocations()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('l5e0b_fbgflexoffice_location'));
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public static function getGroups($locationId = 0)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('l5e0b_fbgflexoffice_location_group'));
        if ($locationId > 0) {
            $query->where($db->quoteName('location_id') . ' = ' . (int) $locationId);
        }
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public static function getRooms($groupId = 0)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('l5e0b_fbgflexoffice_room'));
        if ($groupId > 0) {
            $query->where($db->quoteName('location_group_id') . ' = ' . (int) $groupId);
        }
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public static function getDesks($roomId = 0)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('l5e0b_fbgflexoffice_desk'));
        if ($roomId > 0) {
            $query->where($db->quoteName('room_id') . ' = ' . (int) $roomId);
        }
        $db->setQuery($query);
        return $db->loadObjectList();
    }
}
