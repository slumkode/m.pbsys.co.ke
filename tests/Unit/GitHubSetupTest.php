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

        $this->assertRegExp('/^\d+\.\d+\.\d+$/', $version);
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
        $this->assertFileExists($this->rootPath.'/.github/workflows/deploy-production.yml');
    }

    public function testSelfHelpOwnerDocumentationExists()
    {
        $this->assertFileExists($this->rootPath.'/selfhelp/project-owner-guide.md');
        $this->assertFileExists($this->rootPath.'/selfhelp/server-operations.md');
    }

    public function testSystemdWorkerTemplateExists()
    {
        $this->assertFileExists($this->rootPath.'/deploy/systemd/mpbsys_mpesa_c2b_worker.service');
    }
}
