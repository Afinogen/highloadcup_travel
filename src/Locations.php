<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 11.08.17
 * Time: 16:37
 */

namespace Travel;

/**
 * Места
 *
 * @package Travel
 */
class Locations extends AbstractEntity
{
    /** @var string  */
    protected $_table = 'locations';

    /** @var array  */
    protected $_columns = [
        'distance',
        'city',
        'place',
        'id',
        'country',
    ];

    /**
     * @param array $res
     *
     * @return array
     */
    public function hydrate(array $res): array
    {
        $res['id'] = (int)$res['id'];
        $res['distance'] = (int)$res['distance'];

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
            'fromDate' => 'visited_at > ',
            'toDate'   => 'visited_at < ',
            'fromAge'  => 'users.age >= ',
            'toAge'    => 'users.age < ',
            'gender'   => 'users.gender = '
        ];

        $filter = [];

        foreach ($data as $key => $datum) {
            if (!isset($columns[$key])) {
                return false;
            }

            if ($key === 'gender' && $datum !== 'f' && $datum !== 'm') {
                return false;
            }

            if ($key === 'fromAge' || $key === 'toAge') {
                if (!is_numeric($datum)) {
                    return false;
                }
                $datum = (int)$datum;
            }

            if (($key === 'fromDate' || $key === 'toDate') && !is_numeric($datum)) {
                return false;
            }

            $filter[] = $columns[$key]."'".$datum."'";
        }

        return $filter;
    }

    /**
     * Вычисление средней оценки
     *
     * @param int $id
     * @param array $filter
     *
     * @return bool|float
     */
    public function avg(int $id, array $filter = [])
    {
        $sql = 'select round(AVG(mark), 5) from visits left join users on users.id = visits.user where location = '.$id;
        if (count($filter)) {
            $sql .= ' and '.implode(' and ', $filter);
        }

        $rows = $this->_db->query($sql);

        if (!$rows) {
            return false;
        }

        return (float)$rows->fetchColumn();
    }
}