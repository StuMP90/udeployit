<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    private string $testReposPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Never let tests touch the real mirrors in storage/app/repos.
        $this->testReposPath = sys_get_temp_dir().'/udeployit-test-repos-'.uniqid();
        config(['udeployit.repos_path' => $this->testReposPath]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testReposPath);

        parent::tearDown();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
