<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テストでは外のサービスに問い合わせない。必要なテストだけ Http::fake で偽物の返事を用意する
        Http::preventStrayRequests();

        // 写真は本物のフォルダに書かない（シーダーがサンプルのイラストを置くため。#148）
        Storage::fake('public');
    }
}
