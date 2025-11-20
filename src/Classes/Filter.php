<?php

namespace SQLI\EzToolboxBundle\Classes;

class Filter
{
    public const OPERANDS_MAPPING = [
        "=" => "EQ",
        "!=" => "NEQ",
        "<" => "LT",
        "<=" => "LTE",
        ">" => "GT",
        ">=" => "GTE",
        "LIKE" => "LIKE",
        "NOT LIKE" => "NLIKE",
    ];

    protected $columnName;
    protected $operand;
    protected $value;

    public static function create($columnName, $operand, $value): ?self
    {
        if (array_search($operand, self::OPERANDS_MAPPING)) {
            $filter = new self();

            $filter->columnName = $columnName;
            $filter->operand = $operand;
            $filter->value = $value;

            return $filter;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param mixed $columnName
     */
    public function setColumnName($columnName): void
    {
        $this->columnName = $columnName;
    }

    /**
     * @return mixed
     */
    public function getOperand()
    {
        return $this->operand;
    }

    /**
     * @param mixed $operand
     */
    public function setOperand($operand): void
    {
        $this->operand = $operand;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    private function getOperandsValues()
    {
        return array_values(self::OPERANDS_MAPPING);
    }
}
