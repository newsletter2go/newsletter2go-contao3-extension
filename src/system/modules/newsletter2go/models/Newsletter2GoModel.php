<?php

namespace Contao;

class Newsletter2GoModel
{

    /** @var \Database */
    private $dbInstance = null;

    /** @var Newsletter2GoModel */
    private static $instance = null;

    private function __construct()
    {
        $this->dbInstance = \Database::getInstance();
        $this->dbInstance->prepare("
            CREATE TABLE IF NOT EXISTS tl_newsletter2go (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `value` TEXT,
              PRIMARY KEY (`id`)
            ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;")->execute();
    }

    /**
     * @return Newsletter2GoModel
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Newsletter2GoModel();
        }

        return self::$instance;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool|false $serialize
     */
    public function saveConfigValue($name, $value, $serialize = false)
    {
        $this->dbInstance->prepare('DELETE FROM tl_newsletter2go WHERE `name` = ?')->execute($name);
        $this->dbInstance->prepare('INSERT INTO tl_newsletter2go (`name`, `value`) VALUES (?, ?)')
            ->execute($name, $serialize ? serialize($value) : $value);
    }

    /**
     * @param string $name
     * @param bool|false $deserialize
     * @return string
     */
    public function getConfigValue($name, $deserialize = false)
    {
        $result = $this->dbInstance->prepare('SELECT `value` FROM tl_newsletter2go WHERE `name` = ?')->execute($name);
        if ($result->count() === 0) {
            return null;
        }

        $array = $result->fetchAssoc();

        return $deserialize ? deserialize($array['value']) : $array['value'];
    }

    /**
     * Creates request and returns response.
     *
     * @param string $action
     * @param mixed $post
     * @return array
     */
    public function executeN2Go($action, $post)
    {
        $cURL = curl_init();
        curl_setopt($cURL, CURLOPT_URL, "https://www.newsletter2go.com/en/api/$action/");
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

        $postData = '';
        foreach ($post as $k => $v) {
            $postData .= urlencode($k) . '=' . urlencode($v) . '&';
        }

        $postData = substr($postData, 0, -1);

        curl_setopt($cURL, CURLOPT_POST, 1);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($cURL);
        curl_close($cURL);

        return json_decode($response, true);
    }

    /**
     * Retrieves all member groups
     * @return array
     */
    public function getMemberGroups()
    {
        $result = array(array('id' => 'group_0', 'name' => 'No group', 'description' => 'Members that aren\'t assigned to any group'));
        $query = $this->dbInstance->prepare('SELECT * FROM tl_member_group WHERE disable != 1')->execute();
        $array = $query->fetchAllAssoc();
        foreach ($array as $item) {
            $result[] = array('id' => 'group_' . $item['id'], 'name' => $item['name'], 'description' => '');
        }

        return $result;
    }

    /**
     * Retrieves all newsletter groups
     * @return array
     */
    public function getNewsletterGroups()
    {
        $result = array();
        $query = $this->dbInstance->prepare('SELECT * FROM tl_newsletter_channel')->execute();
        $array = $query->fetchAllAssoc();
        foreach ($array as $item) {
            $result[] = array('id' => 'channel_' . $item['id'], 'name' => $item['title'], 'description' => '');
        }

        return $result;
    }

    /**
     * @param $groupId
     * @param bool $subscribed
     * @return int
     * @throws \Exception
     */
    public function getCustomerCount($groupId, $subscribed = false)
    {
        $count = 0;
        $conditions = array();
        $sql = '';
        $groupBy = '';
        list($type, $id) = explode('_', $groupId);
        if (!$type) {
            throw new \Exception("Group $groupId is invalid!");
        }

        switch ($type) {
            case 'channel':
                $sql = 'SELECT COUNT(DISTINCT(email)) as total FROM tl_newsletter_recipients ';
                if ($id != '-1') {
                    $conditions[] = 'pid = ' . $id;
                } else {
                    $conditions[] = 'email NOT IN (SELECT s.email FROM tl_member s)';
                }

                if ($subscribed) {
                    $conditions[] = 'active = 1';
                }

                break;
            case 'group':
                $groupBy = ' GROUP BY a.id ';
                $sql .= 'SELECT COUNT(*) as total
                         FROM tl_member a
                            LEFT JOIN tl_newsletter_recipients s ON s.email = a.email AND s.active = 1 ';
                if ($id != '-1') {
                    $conditions[] = ($id ? 'a.groups LIKE \'%1:"' . $id . '"%\'' : 'a.groups IS NULL');
                }

                if ($subscribed) {
                    $conditions[] = 's.active = 1';
                }

                break;
            default:
                throw new \Exception("Group type $type is invalid!");
                break;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query = $this->dbInstance->prepare($sql . $groupBy)->execute();
        $result = $query->fetchAssoc();

        return $type === 'group' ? $query->count() : $result['total'];
    }

    /**
     * @param $groupId
     * @param bool $subscribed
     * @param array $fields
     * @param int $limit
     * @param int $offset
     * @param array $emails
     * @return array
     * @throws \Exception
     */
    public function getCustomers($groupId, $subscribed = false, $fields = array(), $limit = 1000, $offset = 0, $emails = array())
    {
        list($type, $id) = explode('_', $groupId);
        $conditions = array();

        if (!$type) {
            throw new \Exception("Group $groupId is invalid!");
        }

        $sql = $this->buildCustomerSql($fields, $type);
        if (!empty($emails)) {
            $conditions[] = 'a.email IN (\'' . implode("','", $emails) . '\')';
        }

        if ($type === 'group') {
            $sql .= ' LEFT JOIN tl_newsletter_recipients s ON s.email = a.email';
            $conditions[] = ($id ? 'groups LIKE \'%1:"' . $id . '"%\'' : 'groups IS NULL');
            if ($subscribed) {
                $conditions[] = 's.active = 1';
            }
        } else {
            $conditions[] = 'a.pid = ' . $id;
            if ($subscribed) {
                $conditions[] = 'a.active = 1';
            }
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        if ($type === 'group') {
            $sql .= ' GROUP BY a.id ';
        }

        if ($limit) {
            $offset = $offset ?: 0;
            $sql .= " LIMIT $offset, $limit";
        }

        $query = $this->dbInstance->prepare($sql)->execute();
        $result = array();
        foreach ($query->fetchAllAssoc() as $item) {
            $email = $item['email'];
            if (array_key_exists('dateOfBirth', $item)) {
                $item['dateOfBirth'] = date("Y-m-d", (int)$item['dateOfBirth']);
            }

            if (array_key_exists('dateAdded', $item)) {
                $item['dateAdded'] = date("Y-m-d", (int)$item['dateAdded']);
            }

            if (array_key_exists('gender', $item)) {
                $item['gender'] = $item['gender'][0];
            }

            if (!in_array('email', $fields)) {
                unset($item['email']);
            }

            $result[$email] = $item;
        }

        return $result;
    }

    /**
     * @param array $fields
     * @param string $type
     * @return string
     * @throws \Exception
     */
    private function buildCustomerSql($fields, $type)
    {
        $table = ($type === 'group' ? 'tl_member a' : ($type === 'channel' ? 'tl_newsletter_recipients a' : null));
        if ($table === null) {
            throw new \Exception("Group type $type is invalid!");
        }

        $selectFields = array('a.email as email');
        foreach ($fields as $field) {
            switch ($field) {
                case 'subscribed':
                    $selectFields[] = ($type === 'group' ? 'IFNULL(s.active, 0) AS ' : 'a.active AS ') . $field;
                    break;
                case 'email':
                    break;
                default:
                    $selectFields[] = ($type === 'group' ? "a.$field AS $field" : "NULL AS $field");
                    break;
            }
        }

        return 'SELECT ' . implode(', ', $selectFields) . ' FROM ' . $table;
    }

}