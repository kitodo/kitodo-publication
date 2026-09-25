<?php
namespace Httpful;

/**
 * No-op drop-in for nategood/httpful's Bootstrap::init(), called once per
 * file under Classes/Services/ImportExternalMetadata/**. The Guzzle-backed
 * shim needs no bootstrapping; kept only so call sites stay untouched.
 * @see Httpful\Request
 */
class Bootstrap
{
    public static function init(): void
    {
    }
}
