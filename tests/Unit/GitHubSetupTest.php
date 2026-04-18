<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GitHubSetupTest extends TestCase
{
    private $rootPath;

    protected function setUp(): void
    {
        $this->rootPath = dirname(__DIR__, 2);
    }

    public function testVersionFileUsesMajorFeatureBugFormat()
    {
        $version = trim(file_get_contents($this->rootPath.'/VERSION'));

        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    public function testEnvironmentTemplateIncludesAppDirectory()
    {
        $envExample = file_get_contents($this->rootPath.'/.env.example');

        $this->assertStringContainsString('APP_DIR=', $envExample);
    }

    public function testGithubWorkflowFilesExist()
    {
        $this->assertFileExists($this->rootPath.'/.github/workflows/laravel-safety.yml');
        $this->assertFileExists($this->rootPath.'/.github/workflows/versioning.yml');
    }
}
