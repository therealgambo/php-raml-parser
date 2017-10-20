<?php

namespace Raml\Parsers;

class RamlFragmentParser
{
    const DOCUMENTATION_ITEM            = 'DocumentationItem';
    const DATA_TYPE                     = 'DataType';
    const NAMED_EXAMPLE                 = 'NamedExample';
    const RESOURCE_TYPE                 = 'ResourceType';
    const RAML_TRAIT                    = 'Trait';
    const ANNOTATION_TYPE_DECLARATION   = 'AnnotationTypeDeclaration';
    const LIBRARY                       = 'Library';
    const OVERLAY                       = 'Overlay';
    const EXTENSION                     = 'Extension';
    const SECURITY_SCHEME               = 'SecurityScheme';
    const RAML_DEFAULT                  = 'Default';

    public static function parse($fragment)
    {
        $fragments = [
            self::DOCUMENTATION_ITEM, self::DATA_TYPE, self::NAMED_EXAMPLE, self::RESOURCE_TYPE,
            self::RAML_TRAIT, self::ANNOTATION_TYPE_DECLARATION, self::LIBRARY, self::OVERLAY,
            self::EXTENSION, self::SECURITY_SCHEME, self::RAML_DEFAULT
        ];

        return in_array($fragment, $fragments) ? $fragment : self::RAML_DEFAULT;
    }
}
