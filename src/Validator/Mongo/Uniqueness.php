<?php
namespace Dsc\Lib\Validator\Mongo;

use Phalcon\Mvc\Model\Validator;

/**
 * Dsc\Lib\Validator\Mongo\Uniqueness
 *
 * Validator for checking uniqueness of field in database
 *
 * <code>
 * $uniqueness = new Uniqueness(
 *     array(
 *         'collection' => 'users',
 *         'field' => 'login', // accepts dot.notation
 *         'message' => 'already taken',
 *     ),
 *     $mongo_connection;
 * );
 * </code>
 *
 * If second parameter will be null (ommited) than validator will try to get database
 * connection from default DI instance with \Phalcon\DI::getDefault()->get('mongo');
 */

class Uniqueness extends Validator
{
    /**
     * Database connection
     * @var Phalcon\Db\Adapter\Pdo
     */
    private $db;

    public function __construct(array $options = array(), $db = null)
    {
        parent::__construct($options);

        if (null === $db) {
            // try to get db instance from default Dependency Injection
            $di = \Phalcon\DI::getDefault();

            if ($di instanceof \Phalcon\DiInterface && $di->has('mongo')) {
                $db = $di->get('mongo');
            }
        }

        if ($db instanceof \MongoDb) {
            $this->db = $db;
        } else {
            throw new ValidationException('Validator Uniqueness requires a connection to the database');
        }

        if (false === $this->isSetOption('collection')) {
            throw new ValidationException('Validator requires collection option to be set');
        }

        if (false === $this->isSetOption('field')) {
            throw new ValidationException('Validator requires field option to be set');
        }
    }

    /**
     * Executes the uniqueness validation
     *
     * @param  Phalcon\Mvc\Collection $model
     * @param  string             $attribute
     * @return boolean
     */
    public function validate($model)
    {
        $collection = $this->getOption('collection');
        $field = $this->getOption('field');
        $value = $model->{$field};
        
        $count = $this->db->selectCollection($collection)->count(array($field => $value)) > 0;
        
        if ($count) {
            $message = $this->getOption('message');

            if (null === $message) {
                $message = 'Already taken. Choose another!';
            }

            $this->appendMessage($message, $field, 'Uniqueness');

            return false;
        }

        return true;
    }
}