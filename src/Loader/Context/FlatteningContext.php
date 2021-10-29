<?php
declare(strict_types=1);

namespace Soap\Wsdl\Loader\Context;

final class FlatteningContext
{
    /** @var array<string, true> */
    private $imports = [];

    public function isImported(string $location): bool
    {
        return array_key_exists($location, $this->imports);
    }

    /**
     * Announce a new import and decide whether it needs to be imported or not.
     */
    public function announceImport(string $location): bool
    {
        $exists = array_key_exists($location, $this->imports);
        if ($exists) {
            return false;
        }

        $this->imports[$location] = true;

        return true;
    }
}
