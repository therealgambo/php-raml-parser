<?php

namespace Raml\Parsers;

use Raml\Exception\RamlParserException;

class RamlHeaderParser
{
    const RAML_HEADER_REGEX = '/^#%RAML\s(?<version>\d.\d){1}\s?(?<fragment>[a-zA-Z]+)?$/';

    private $version;
    private $fragment;

    public function __construct(RamlVersionParser $version, $fragment = null)
    {
        $this->version = $version->getVersion();
        $this->fragment = $fragment;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getFragment()
    {
        return $this->fragment;
    }

    public static function parse($string)
    {
        if (preg_match(self::RAML_HEADER_REGEX, $string, $matches)) {
            $version = RamlVersionParser::parse($matches['version']);

            if ($version->getVersion() === RamlVersionParser::RAML_10) {
                if (isset($matches['fragment'])) {
                    $fragment = RamlFragmentParser::parse($matches['fragment']);
                    if (is_null($fragment)) {
                        throw new RamlParserException('Invalid RAML header fragment: ' . $matches['fragment']);
                    }
                } else {
                    $fragment = null;
                }

                return new RamlHeaderParser($version, $fragment);
            } else {
                return new RamlHeaderParser($version);
            }
        }

        throw new RamlParserException('Invalid RAML header: ' . $string);
    }
}
