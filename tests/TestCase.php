<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テストでは外のサービスに問い合わせない。必要なテストだけ Http::fake で偽物の返事を用意する
        Http::preventStrayRequests();
    }
}
