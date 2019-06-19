<?php

namespace Scaleplan\Result;

use Scaleplan\Helpers\NameConverter;
use Scaleplan\Model\Model;
use Scaleplan\Result\Exceptions\ClassIsNotModelException;
use Scaleplan\Result\Exceptions\ResultException;
use Scaleplan\Result\Interfaces\DbResultInterface;

/**
 * Класс результата запроса к БД
 *
 * Class DbResult
 *
 * @package Scaleplan\Result
 */
class DbResult extends ArrayResult implements DbResultInterface
{
    /**
     * @var string|null
     */
    protected $modelClass;

    /**
     * DbResult constructor.
     *
     * @param array|null $result
     * @param string|null $prefix
     *
     * @throws ResultException
     */
    public function __construct(?array $result, string $prefix = null)
    {
        parent::__construct($result);
        $this->setResult($result, $prefix);
    }

    /**
     * @param string|null $modelClass
     */
    public function setModelClass(?string $modelClass) : void
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Установить результат
     *
     * @param $result - результат
     * @param string|null $prefix - префикс полей результата
     *
     * @throws ResultException
     */
    public function setResult(?array $result, string $prefix = null) : void
    {
        if ($result === null) {
            $this->result = $result;
            return;
        }

        if (!empty($result[0]) && !\is_array($result[0])) {
            throw new ResultException('Входной массив не является результатом запроса к РСУБД');
        }

        if ($prefix) {
            foreach ($result as $record) {
                foreach ($record as $key => $value) {
                    $record["{$prefix}_$key"] = $value;
                    unset($record[$key]);
                }
            }
        }

        $this->result = $result;
    }

    /**
     * Вернуть первую запись результата
     *
     * @return array
     */
    public function getFirstResult() : ?array
    {
        return !empty($this->result[0]) && \is_array($this->result[0]) ? $this->result[0] : null;
    }

    /**
     * Вернуть поле id первой записи результата
     *
     * @return mixed|null
     */
    public function getResultId()
    {
        return $this->result[0]['id'] ?? null;
    }

    /**
     * Вернуть первое поле первой записи результата
     *
     * @return mixed
     */
    public function getResultFirstField()
    {
        if ($firstResult = $this->getFirstResult()) {
            return reset($firstResult);
        }

        return null;
    }

    /**
     * @param array|null $array
     *
     * @return Model|null
     *
     * @throws ClassIsNotModelException
     */
    protected function arrayToObject(?array $array) : ?Model
    {
        if ($array === null) {
            return null;
        }

        $propertyArray = [];
        foreach ($array as $property => $value) {
            $newPropertyName = NameConverter::snakeCaseToLowerCamelCase($property);
            $propertyArray[$newPropertyName] = $value;
        }

        if (!empty($this->modelClass)) {
            if (!is_subclass_of($this->modelClass, Model::class)) {
                throw new ClassIsNotModelException();
            }

            return new $this->modelClass($propertyArray);
        }

        return new Model($propertyArray);
    }

    /**
     * @return null|Model[]
     */
    public function getObjectResult() : ?array
    {
        if ($this->getArrayResult() === null) {
            return null;
        }

        return array_map(static function(array $row) {
            return $this->arrayToObject($row);
        }, $this->getArrayResult());
    }

    /**
     * Возвратить результат в виде объекта
     *
     * @return Model|null
     *
     * @throws ClassIsNotModelException
     */
    public function getFirstObjectResult() : ?Model
    {
        return $this->arrayToObject($this->getFirstResult());
    }
}
