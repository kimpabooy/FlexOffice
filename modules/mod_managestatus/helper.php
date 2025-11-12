<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Session\Session;

class ModManageStatusHelper
{
    public static function handleRequest()
    {
        // Enkel POST-hantering: kontrollera token och insertera en statusperiod
        $input = Factory::getApplication()->input;

        if ($input->getMethod() !== 'POST') {
            return;
        }

        if (!Session::checkToken('post')) {
            // invalid token
            return;
        }

        $deskId = $input->getInt('desk_id');
        $statusId = $input->getInt('status_id');
        $start = $input->getString('start_time');
        $end = $input->getString('end_time');
        $userId = Factory::getUser()->id ?: null;

        if (!$deskId || !$statusId || !$start) {
            // enkla valideringar - kräver desk, status och start. end_time kan vara NULL
            return;
        }

        // Spara i DB
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        // Konvertera starttid alltid; endtid kan vara tom och lagras som NULL
        $startTime = date('Y-m-d H:i:s', strtotime($start));
        $endTime = null;
        if (!empty($end)) {
            $endTime = date('Y-m-d H:i:s', strtotime($end));
        }

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__status_period'))
            ->columns([
                $db->quoteName('desk_id'),
                $db->quoteName('status_id'),
                $db->quoteName('start_time'),
                $db->quoteName('end_time'),
                $db->quoteName('created_by'),
                $db->quoteName('created_at'),
            ]);

        // Bygg values där end_time och created_by kan vara NULL
        $values = [
            (int) $deskId,
            (int) $statusId,
            $db->quote($startTime),
            $endTime !== null ? $db->quote($endTime) : 'NULL',
            $userId !== null ? (int) $userId : 'NULL',
            $db->quote(date('Y-m-d H:i:s')),
        ];

        $query->values(implode(',', $values));

        $db->setQuery($query);

        try {
            $db->execute();
            // redirect för att undvika repost (enkel refresh)
            $app = Factory::getApplication();
            $app->redirect(Factory::getApplication()->input->server->getString('REQUEST_URI'));
        } catch (Exception $e) {
            // Logga vid fel
            if (class_exists('Joomla\\CMS\\Log\\Log')) {
                \Joomla\CMS\Log\Log::add($e->getMessage(), \Joomla\CMS\Log\Log::ERROR, 'mod_manageStatus');
            }
            return;
        }
    }

    public static function getStatusPeriods()
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        // Läs statusperioder från status_period-tabellen och visa vem som skapade posten
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('s.id'),
                $db->quoteName('s.desk_id'),
                $db->quoteName('s.status_id'),
                $db->quoteName('s.start_time'),
                $db->quoteName('s.end_time'),
                $db->quoteName('u.name') . ' AS ' . $db->quoteName('created_by_name'),
            ])
            ->from($db->quoteName('#__status_period', 's'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.created_by'))
            ->order($db->quoteName('s.start_time') . ' DESC');

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (Exception $e) {
            return [];
        }
    }

    public static function getDesks()
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        // Hämta desks med room namn om möjligt
        $query = $db->getQuery(true)
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
            return [];
        }
    }

    public static function getStatusTypes()
    {
        if (method_exists(Factory::class, 'getContainer')) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
        } else {
            $db = Factory::getDbo();
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'name']))
            ->from($db->quoteName('#__status'))
            ->order($db->quoteName('id'));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (Exception $e) {
            return [];
        }
    }
}
