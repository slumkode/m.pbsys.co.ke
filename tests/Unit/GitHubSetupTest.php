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

    public function testLoginActivityTrackingFilesExist()
    {
        $this->assertFileExists($this->rootPath.'/app/Models/UserLoginActivity.php');
        $this->assertFileExists($this->rootPath.'/app/Services/UserLoginActivityLogger.php');
        $this->assertFileExists($this->rootPath.'/app/Http/Middleware/TrackAuthenticatedUserActivity.php');
        $this->assertFileExists($this->rootPath.'/app/Http/Controllers/UserLocationController.php');
    }

    public function testLoginFormUsesLaravelRememberField()
    {
        $loginView = file_get_contents($this->rootPath.'/resources/views/auth/login.blade.php');

        $this->assertStringContainsString('name="remember"', $loginView);
        $this->assertStringContainsString('checked', $loginView);
    }

    public function testSidebarDoesNotExposeApiDocumentationLink()
    {
        $sidebarView = file_get_contents($this->rootPath.'/resources/views/admin/includes/sidebar.blade.php');

        $this->assertStringNotContainsString("route('documentation.index')", $sidebarView);
        $this->assertStringNotContainsString('API Documentation', $sidebarView);
    }

    public function testSidebarUsesTextBrandInsteadOfLogoImage()
    {
        $sidebarView = file_get_contents($this->rootPath.'/resources/views/admin/includes/sidebar.blade.php');

        $this->assertStringContainsString('Talash Enterprises', $sidebarView);
        $this->assertStringNotContainsString('assets/img/taifa.jpg', $sidebarView);
        $this->assertStringNotContainsString('<img', $sidebarView);
    }

    public function testDocumentationPermissionIsNotRegistered()
    {
        $userModel = file_get_contents($this->rootPath.'/app/User.php');
        $accessMigration = file_get_contents($this->rootPath.'/database/migrations/2026_04_10_000100_create_advanced_access_control_tables.php');
        $internalRoleMigration = file_get_contents($this->rootPath.'/database/migrations/2026_04_10_000200_add_internal_visibility_permissions.php');

        $this->assertStringNotContainsString("'documentation'", $userModel);
        $this->assertStringNotContainsString('documentation.view', $userModel);
        $this->assertStringNotContainsString('documentation.view', $accessMigration);
        $this->assertStringNotContainsString('documentation.view', $internalRoleMigration);
    }

    public function testDocumentationPermissionRemovalMigrationExists()
    {
        $this->assertFileExists($this->rootPath.'/database/migrations/2026_04_19_000000_remove_documentation_permission.php');
    }

    public function testProductionDeployReloadsWebRuntime()
    {
        $workflow = file_get_contents($this->rootPath.'/.github/workflows/deploy-production.yml');

        $this->assertStringContainsString('reload_web_runtime', $workflow);
        $this->assertStringContainsString('systemctl reload', $workflow);
    }

    public function testTransactionReportsIncludeKeywordFilter()
    {
        $reportView = file_get_contents($this->rootPath.'/resources/views/admin/modules/transaction-reports.blade.php');
        $reportController = file_get_contents($this->rootPath.'/app/Http/Controllers/TransactionReportController.php');

        $this->assertStringContainsString('report-keyword-id', $reportView);
        $this->assertStringContainsString('keywordOptions', $reportController);
        $this->assertStringContainsString("d.keyword_id = $('#report-keyword-id').val();", $reportView);
        $this->assertStringContainsString('$this->transactionKeywordRuleForUser($authUser, $keywordId)', $reportController);
        $this->assertStringContainsString('$this->applyAccountKeywordTransactionRule($query, $keywordRule);', $reportController);
    }

    public function testReportingPerformanceIndexesExist()
    {
        $this->assertFileExists($this->rootPath.'/database/migrations/2026_04_19_001000_add_transaction_reporting_indexes.php');

        $migration = file_get_contents($this->rootPath.'/database/migrations/2026_04_19_001000_add_transaction_reporting_indexes.php');

        $this->assertStringContainsString('transactions_time_reporting_index', $migration);
        $this->assertStringContainsString('transactions_service_time_reporting_index', $migration);
        $this->assertStringContainsString('transactions_time_service_reporting_index', $migration);
        $this->assertStringContainsString('transactions_account_reporting_index', $migration);
    }

    public function testDashboardReportCardsUseAggregateHelpers()
    {
        $paymentsController = file_get_contents($this->rootPath.'/app/Http/Controllers/Payments.php');

        $this->assertStringContainsString('dashboardSummaryStats', $paymentsController);
        $this->assertStringContainsString('dashboardServiceReportStats', $paymentsController);
        $this->assertStringContainsString('dashboardKeywordReportStats', $paymentsController);
        $this->assertStringContainsString('groupBy(\'shortcode_id\', \'type\')', $paymentsController);
    }

    public function testKeywordAccountMatchingCanUseAccountIndex()
    {
        $baseController = file_get_contents($this->rootPath.'/app/Http/Controllers/Controller.php');

        $this->assertStringContainsString('account LIKE ?', $baseController);
        $this->assertStringNotContainsString('LOWER(account) LIKE ?', $baseController);
    }

    public function testBrowserLocationUnavailableIsAudited()
    {
        $routes = file_get_contents($this->rootPath.'/routes/web.php');
        $locationController = file_get_contents($this->rootPath.'/app/Http/Controllers/UserLocationController.php');
        $loginLogger = file_get_contents($this->rootPath.'/app/Services/UserLoginActivityLogger.php');
        $adminJs = file_get_contents($this->rootPath.'/resources/views/admin/includes/js.blade.php');

        $this->assertStringContainsString("user-location.unavailable", $routes);
        $this->assertStringContainsString('recordBrowserLocationUnavailable', $locationController);
        $this->assertStringContainsString('location_unavailable', $loginLogger);
        $this->assertStringContainsString('permission_denied', $adminJs);
        $this->assertStringContainsString('position_unavailable', $adminJs);
        $this->assertStringContainsString('timeout', $adminJs);
    }

    public function testIpLocationFallbackIsConfigurable()
    {
        $this->assertFileExists($this->rootPath.'/app/Services/IpLocationResolver.php');

        $servicesConfig = file_get_contents($this->rootPath.'/config/services.php');
        $envExample = file_get_contents($this->rootPath.'/.env.example');
        $loginLogger = file_get_contents($this->rootPath.'/app/Services/UserLoginActivityLogger.php');

        $this->assertStringContainsString('IP_LOCATION_ENDPOINT', $servicesConfig);
        $this->assertStringContainsString('IP_LOCATION_ENDPOINT=', $envExample);
        $this->assertStringContainsString('approximate_ip_location', $loginLogger);
    }
}
