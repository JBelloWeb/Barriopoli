<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Base MySQL de pruebas (phpunit.xml): las consultas espaciales del PLAN-03 no existen en SQLite.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
