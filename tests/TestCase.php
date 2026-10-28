<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Les logos copiés à l'envoi ou téléversés ne doivent jamais arriver dans le vrai storage/app/private.
        Storage::fake('local');
    }
}
