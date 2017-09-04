<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 11.08.17
 * Time: 16:37
 */

namespace Travel;

/**
 * Class Db
 *
 * @package Travel
 */
class Db
{
    /** @var  \PDO */
    protected $_pdo;

    /**
     * Db constructor.
     *
     * @param string $dns
     * @param string $user
     * @param string|null $pass
     */
    public function __construct($dns, $user, $pass)
    {
        $this->_pdo = new \PDO(
            $dns, $user, $pass, [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                \PDO::ATTR_PERSISTENT         => true
            ]
        );
//        $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param $s
     *
     * @return \PDOStatement
     */
    public function prepare($s): \PDOStatement
    {
        return $this->_pdo->prepare($s);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_pdo->errorInfo();
    }

    /**
     * @return string
     */
    public function getLastId()
    {
        return $this->_pdo->lastInsertId();
    }

    /**
     * @param $s
     *
     * @return \PDOStatement
     */
    public function query($s): \PDOStatement
    {
        return $this->_pdo->query($s);
    }
}