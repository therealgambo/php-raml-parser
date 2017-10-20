<?php

namespace Raml\Parsers;

use Raml\Exception\RamlParserException;

class RamlVersionParser
{
    const RAML_08 = '0.8';
    const RAML_10 = '1.0';

    private $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public static function parse($string)
    {
        if (in_array($string, [self::RAML_08, self::RAML_10])) {
            return new RamlVersionParser($string);
        }

        throw new RamlParserException('Invalid RAML version: ' . $string);
    }
}