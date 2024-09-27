<?php declare(strict_types=1);

namespace Soap\Wsdl\Xml\Xmlns;

use DOMElement;

/**
 * @see https://gist.github.com/veewee/32c3aa94adcf878700a9d5baa4b2a2de
 *
 * PHP does an optimization of namespaces during `importNode()`.
 * In some cases, this causes the root xmlns to be removed from the imported node which could lead to xsd qname errors.
 *
 * This function tries to re-add the root xmlns if it's available on the source but not on the target.
 *
 * It will most likely be solved in PHP 8.4's new spec compliant DOM\XMLDocument implementation.
 * @see https://github.com/php/php-src/pull/13031
 *
 * For now, this will do the trick.
 */
final class FixRemovedDefaultXmlnsDeclarationsDuringImport
{
    public function __invoke(DOMElement $target, DOMElement $source): void
    {
        if (!$source->getAttribute('xmlns') || $target->hasAttribute('xmlns')) {
            return;
        }

        $target->setAttribute('xmlns', $source->getAttribute('xmlns'));
    }
}
