<?php

namespace Raml\Type;

use Raml\Type;
use Raml\Exception\InvalidTypeException;

/**
 * DateTimeOnlyType class
 *
 * @author Melvin Loos <m.loos@infopact.nl>
 */
class DateTimeOnlyType extends Type
{
    const TYPE_NAME = 'datetime-only';

    /**
    * Create a new DateTimeOnlyType from an array of data
    *
    * @param string    $name
    * @param array     $data
    *
    * @return DateTimeOnlyType
    */
    public static function createFromArray($name, array $data = [])
    {
        $type = parent::createFromArray($name, $data);

        return $type;
    }

    public function validate($value)
    {
        $format = \DateTime::RFC3339;
        $d = DateTime::createFromFormat($format, $value);
        if (($d && $d->format($format) === $value) === false) {
            throw new InvalidTypeException(['property' => $this->name, 'constraint' => sprintf('Value is not conform format: %s.', $format)]);
        }
        return true;
    }
}
