<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 16.08.17
 * Time: 9:22
 */

namespace Travel;

/**
 * Class AbstractEntity
 *
 * @package Travel
 */
abstract class AbstractEntity
{
    /** @var  \PDO */
    protected $_db;

    /** @var  string Название таблицы */
    protected $_table;

    /** @var  array Столбцы таблицы */
    protected $_columns;

    /**
     * AbstractEntity constructor.
     *
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->_db = $db;
    }

    /**
     * Вставка большого массива данных
     *
     * @param array $values
     *
     * @return bool
     */
    public function multiInsert(array $values): bool
    {
        $columns = $this->_columns;
        sort($columns);
        $sql = 'INSERT INTO `'.$this->_table.'` ('.implode(',', $columns).') VALUES ';

        $paramArray = [[]];
        $sqlArray = [];
        $sqlQuery = '('.implode(',', array_fill(0, count($columns), '?')).')';
        foreach ($values as $row) {
            $sqlArray[] = $sqlQuery;

            ksort($row);
            $paramArray[] = array_values($row);
        }

        $sql .= implode(',', $sqlArray);
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(array_merge(...$paramArray));
    }

    /**
     * Вставка одной строки
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values): bool
    {
        $columns = array_keys($values);

        $sql = 'INSERT INTO `'.$this->_table.'` ('.implode(',', $columns).') VALUES ';

        $paramArray = [];
        $sqlArray = [];

        foreach ($values as $row) {
            $sqlArray[] = implode(',', array_fill(0, count($row), '?'));
            $paramArray[] = $row;
        }

        $sql .= '('.implode(',', $sqlArray).')';

        $stmt = $this->_db->prepare($sql);

        return $stmt->execute($paramArray);
    }

    /**
     * Валидация полей запроса
     *
     * @param array $data
     * @param bool $dropId
     *
     * @return bool
     */
    public function checkFields(array $data, $dropId = true): bool
    {
        $columns = array_flip($this->_columns);
        if ($dropId) {
            unset($columns['id']);
        }

        foreach ($data as $key => $datum) {
            if (!isset($columns[$key]) || $datum === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Обновление данных по id
     *
     * @param array $data
     * @param int $id
     *
     * @return bool
     */
    public function update(array $data, int $id): bool
    {
        $sql = 'update '.$this->_table.' set ';
        $sets = [];

        foreach ($data as $key => $val) {
            $sets[] = $key.'=:'.$key;
        }
        $sql .= implode(',', $sets).' where id = :id';
        $stmt = $this->_db->prepare($sql);
        $data['id'] = $id;

        return $stmt->execute($data);
    }

    /**
     * Поиск элемента по id
     *
     * @param int $id
     *
     * @return bool|mixed
     */
    public function findById(int $id)
    {
        $sql = 'SELECT '.implode(',', $this->_columns).' FROM '.$this->_table.' where id = '.$id.' LIMIT 1;';
        $rows = $this->_db->query($sql);
        if (!$rows) {
            return false;
        }

        return $rows->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Проверка существования элемента
     *
     * @param int $id
     *
     * @return bool
     */
    public function hasItem(int $id): bool
    {
        $sql = 'SELECT * FROM '.$this->_table.' WHERE id = '.$id.' LIMIT 1;';
        $rows = $this->_db->query($sql);

        if (!$rows) {
            return false;
        }

        return (bool)$rows->rowCount();
    }

    /**
     * Преобразование элементов к нужным типам после выборки из бд
     *
     * @param array $res
     *
     * @return array
     */
    public function hydrate(array $res): array
    {
        return $res;
    }

    /**
     * Получение фильтра для БД на основе GET запроса
     *
     * @param array $data
     *
     * @return array|bool
     */
    public function getFilter(array $data)
    {
        return [];
    }
}