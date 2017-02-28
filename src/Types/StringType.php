<?php

namespace Raml\Types;


use Raml\Type;


/**
 * StringType class
 *
 * @author Melvin Loos <m.loos@infopact.nl>
 */
class StringType extends Type
{
    /**
     * Regular expression that this string should match.
     *
     * @var string
     **/
    private $pattern;

    /**
     * Minimum length of the string. Value MUST be equal to or greater than 0.
     * Default: 0
     *
     * @var int
     **/
    private $minLength;

    /**
     * Maximum length of the string. Value MUST be equal to or greater than 0.
     * Default: 2147483647
     *
     * @var int
     **/
    private $maxLength;

    /**
    * Create a new StringType from an array of data
    *
    * @param string    $name
    * @param array     $data
    *
    * @return StringType
    */
    public static function createFromArray($name, array $data = [])
    {
        $type = parent::createFromArray($name, $data);
        /* @var $type StringType */

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'pattern':
                    $type->setPattern($value);
                    break;
                case 'minLength':
                    $type->setMinLength($value);
                    break;
                case 'maxLength':
                    $type->setMaxLength($value);
                    break;
            }
        }
        
        return $type;
    }

    /**
     * Get the value of Pattern
     *
     * @return mixed
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set the value of Pattern
     *
     * @param mixed $pattern
     *
     * @return self
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get the value of Min Length
     *
     * @return mixed
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * Set the value of Min Length
     *
     * @param mixed $minLength
     *
     * @return self
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * Get the value of Max Length
     *
     * @return mixed
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * Set the value of Max Length
     *
     * @param mixed $maxLength
     *
     * @return self
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;

        return $this;
    }
}