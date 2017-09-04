<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 11.08.17
 * Time: 16:37
 */

namespace Travel;

/**
 * Посещения
 *
 * @package Travel
 */
class Visits extends AbstractEntity
{
    /** @var string  */
    protected $_table = 'visits';

    /** @var array  */
    protected $_columns = [
        'user',
        'location',
        'visited_at',
        'id',
        'mark',
    ];

    /**
     * @param array $res
     *
     * @return array
     */
    public function hydrate(array $res): array
    {
        $res['visited_at'] = (int)$res['visited_at'];
        $res['mark'] = (int)$res['mark'];
        $res['id'] = (int)$res['id'];
        $res['location'] = (int)$res['location'];
        $res['user'] = (int)$res['user'];

        return $res;
    }
}