<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 11.08.17
 * Time: 16:37
 */

namespace Travel;

/**
 * Пользователи
 *
 * @package Travel
 */
class Users extends AbstractEntity
{
    protected $_table = 'users';

    /**
     * @var array
     */
    protected $_columns = [
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'id',
        'email',
    ];

    /**
     * AbstractEntity constructor.
     *
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        parent::__construct($db);
//        $this->_cache = new \Memcached();
//        $this->_cache->addServer('127.0.0.1', 11211);
    }

    /**
     * @param array $res
     *
     * @return array
     */
    public function hydrate(array $res): array
    {
        $res['id'] = (int)$res['id'];
        $res['birth_date'] = (int)$res['birth_date'];

        return $res;
    }

    /**
     * @param array $data
     *
     * @return array|bool
     */
    public function getFilter(array $data)
    {
        $columns = [
            'fromDate'   => 'visited_at > ',
            'toDate'     => 'visited_at < ',
            'country'    => 'country = ',
            'toDistance' => 'distance < ',
        ];

        $filter = [];

        foreach ($data as $key => $datum) {
            if (!isset($columns[$key])) {
                return false;
            }

            if (($key === 'fromDate' || $key === 'toDate' || $key === 'toDistance') && !is_numeric($datum)) {
                return false;
            }
            $filter[] = $columns[$key]."'".$datum."'";
        }

        return $filter;
    }

    /**
     * @param int $id
     * @param array $filter
     *
     * @return array|bool
     */
    public function visits(int $id, array $filter = [])
    {
        $sql = 'select mark, visited_at, place from visits LEFT JOIN locations ON locations.id = visits.location where user = '.$id;

        if (count($filter)) {
            $sql .= ' and '.implode(' and ', $filter);
        }

        $sql .= ' order by visited_at asc';

        $rows = $this->_db->query($sql);

        if (!$rows) {
            return false;
        }

        $items = $rows->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item['mark'] = (int)$item['mark'];
            $item['visited_at'] = (int)$item['visited_at'];
        }

        return $items;
    }

    /**
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return false|string
     */
    public static function getAge($y, $m, $d)
    {
        if ($m > date('m', TEST_TIMESTAMP) || ($m == date('m', TEST_TIMESTAMP) && $d > date('d', TEST_TIMESTAMP))) {
            return (date('Y', TEST_TIMESTAMP) - $y - 1);
        }

        return (date('Y', TEST_TIMESTAMP) - $y);
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    public function multiInsert(array $values): bool
    {
        $columns = $this->_columns;
        $columns[] = 'age';
        sort($columns);
        $sql = 'INSERT INTO `'.$this->_table.'` ('.implode(',', $columns).') VALUES ';

        $paramArray = [[]];
        $sqlArray = [];

        $sqlQuery = '('.implode(',', array_fill(0, count($columns), '?')).')';
        foreach ($values as $row) {
            $sqlArray[] = $sqlQuery;

//            $oldRow = $row;
            $d = explode('-', date('d-m-Y', $row['birth_date']));
            $row['age'] = self::getAge((int)$d[2], (int)$d[1], (int)$d[0]);
            ksort($row);
//            $this->_cache->add($this->_table.$row['id'], $oldRow);
            $paramArray[] = array_values($row);
        }

        $sql .= implode(',', $sqlArray);
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(array_merge(...$paramArray));
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values): bool
    {
//        $oldValues = $values;
        if (isset($values['birth_date'])) {
            $d = explode('-', date('d-m-Y', $values['birth_date']));
            $values['age'] = self::getAge((int)$d[2], (int)$d[1], (int)$d[0]);
        }

        $columns = array_keys($values);

        $sql = 'INSERT INTO `'.$this->_table.'` ('.implode(',', $columns).') VALUES ';

        $paramArray = [];
        $sqlArray = [];
//        $this->_cache->add($this->_table.$values['id'], $oldValues);
        foreach ($values as $row) {
            $sqlArray[] = implode(',', array_fill(0, count($row), '?'));
            $paramArray[] = $row;
        }

        $sql .= '('.implode(',', $sqlArray).')';

        $stmt = $this->_db->prepare($sql);

        return $stmt->execute($paramArray);
    }

    /**
     * @param array $data
     * @param int $id
     *
     * @return bool
     */
    public function update(array $data, int $id): bool
    {
        if (isset($data['birth_date'])) {
            $d = explode('-', date('d-m-Y', $data['birth_date']));
            $data['age'] = self::getAge((int)$d[2], (int)$d[1], (int)$d[0]);
        }

        $sql = 'update '.$this->_table.' set ';
        $sets = [];
//        $keyM = $this->_table.$id;
//        $old = $this->_cache->get($keyM);
        foreach ($data as $key => $val) {
            $sets[] = $key.'=:'.$key;
//            if ($key !== 'age') {
//                $old[$key] = $val;
//            }
        }
        $sql .= implode(',', $sets).' where id = :id';
        $stmt = $this->_db->prepare($sql);
        $data['id'] = $id;

//        $this->_cache->set($keyM, $old);

        return $stmt->execute($data);
    }
//
//    /**
//     * @param int $id
//     *
//     * @return bool|mixed
//     */
//    public function findById(int $id)
//    {
//        return $this->_cache->get($this->_table.$id);
//    }
//
//    /**
//     * @param int $id
//     *
//     * @return bool
//     */
//    public function hasItem(int $id): bool
//    {
//        return (bool)$this->_cache->get($this->_table.$id);
//    }
}