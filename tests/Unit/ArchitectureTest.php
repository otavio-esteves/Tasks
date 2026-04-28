<?php

namespace Tests\Unit;

use Tests\TestCase;

class ArchitectureTest extends TestCase
{
    /**
     * These tests intentionally use string scans instead of external architecture libraries.
     * They are lightweight guards against obvious regressions, not full dependency graph analysis.
     */
    public function test_domain_layer_does_not_depend_on_forbidden_frameworks_or_models(): void
    {
        foreach ($this->phpFilesIn(app_path('Domain')) as $file) {
            $this->assertFileDoesNotContainAny(
                $file,
                [
                    'Livewire\\',
                    'App\\Livewire\\',
                    'Illuminate\\Http\\',
                    'Illuminate\\Routing\\',
                    'App\\Models\\',
                    'Illuminate\\Database\\Eloquent\\',
                    'use Eloquent',
                    'extends Model',
                ],
            );
        }
    }

    public function test_application_layer_does_not_depend_on_ui_http_or_controllers(): void
    {
        foreach ($this->phpFilesIn(app_path('Application')) as $file) {
            $this->assertFileDoesNotContainAny(
                $file,
                [
                    'Livewire\\',
                    'App\\Livewire\\',
                    'Illuminate\\Support\\Facades\\Blade',
                    'Illuminate\\View\\',
                    'App\\Http\\Controllers\\',
                    'Illuminate\\Http\\Request',
                    'Illuminate\\Foundation\\Http\\FormRequest',
                    'App\\Http\\Requests\\',
                ],
            );
        }
    }

    public function test_livewire_components_do_not_contain_direct_eloquent_queries_when_use_case_exists(): void
    {
        $filesToGuard = [
            app_path('Livewire/Admin/CategoryManager.php'),
            app_path('Livewire/Admin/SecretariatManager.php'),
            app_path('Livewire/Secretariat/ServiceOrderManager.php'),
        ];

        $forbiddenFragments = [
            '::query(',
            '::where(',
            '::find(',
            '::findOrFail(',
            '::create(',
            '::updateOrCreate(',
            '->where(',
            '->latest(',
            '->paginate(',
            '->orderBy(',
        ];

        foreach ($filesToGuard as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents);

            foreach ($forbiddenFragments as $fragment) {
                $this->assertStringNotContainsString($fragment, $contents, "{$file} contains {$fragment}");
            }
        }
    }

    public function test_livewire_components_do_not_instantiate_critical_models_directly_when_use_case_exists(): void
    {
        $filesToGuard = [
            app_path('Livewire/Admin/CategoryManager.php'),
            app_path('Livewire/Admin/SecretariatManager.php'),
            app_path('Livewire/Secretariat/ServiceOrderManager.php'),
        ];

        foreach ($filesToGuard as $file) {
            $this->assertFileDoesNotContainAny(
                $file,
                [
                    'new Category(',
                    'new Secretariat(',
                    'new ServiceOrder(',
                ],
            );
        }
    }

    public function test_critical_use_cases_have_feature_coverage_for_creation_update_authorization_and_scope_rules(): void
    {
        $expectations = [
            [
                'use_case' => app_path('Application/ServiceOrders/CreateServiceOrder.php'),
                'tests' => [
                    base_path('tests/Feature/ServiceOrders/ServiceOrderDomainTest.php'),
                    base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/ServiceOrders/UpdateServiceOrder.php'),
                'tests' => [
                    base_path('tests/Feature/ServiceOrders/ServiceOrderDomainTest.php'),
                    base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/ServiceOrders/DeleteServiceOrder.php'),
                'tests' => [
                    base_path('tests/Feature/ServiceOrders/ServiceOrderDomainTest.php'),
                    base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/ServiceOrders/GetServiceOrder.php'),
                'tests' => [
                    base_path('tests/Feature/ServiceOrders/ServiceOrderDomainTest.php'),
                    base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/ServiceOrders/Queries/ListServiceOrders.php'),
                'tests' => [
                    base_path('tests/Feature/Listings/ServiceOrderListingTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/ServiceOrders/Validators/EnsureCategoryBelongsToSecretariat.php'),
                'tests' => [
                    base_path('tests/Feature/ServiceOrders/ServiceOrderDomainTest.php'),
                    base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/Categories/SaveCategory.php'),
                'tests' => [
                    base_path('tests/Feature/Admin/CategoryManagerTest.php'),
                ],
            ],
            [
                'use_case' => app_path('Application/Secretariats/SaveSecretariat.php'),
                'tests' => [
                    base_path('tests/Feature/Admin/SecretariatManagerTest.php'),
                ],
            ],
        ];

        foreach ($expectations as $expectation) {
            $this->assertFileExists($expectation['use_case']);

            foreach ($expectation['tests'] as $testFile) {
                $this->assertFileExists($testFile);
            }
        }
    }

    public function test_status_transition_rules_have_dedicated_feature_coverage(): void
    {
        $testFile = base_path('tests/Feature/ServiceOrders/ServiceOrderDomainTest.php');

        $this->assertFileExists($testFile);
        $this->assertFileContainsAll(
            $testFile,
            [
                'test_service_order_status_transition_is_flexible',
                'changeStatus(',
            ],
        );
    }

    public function test_authorization_has_dedicated_feature_coverage_for_protected_routes_and_livewire_actions(): void
    {
        $files = [
            base_path('tests/Feature/Authorization/AccessControlTest.php'),
            base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file);
        }

        $this->assertFileContainsAll(
            base_path('tests/Feature/Authorization/AccessControlTest.php'),
            [
                'assertForbidden()',
                "assertRedirect(route('login'))",
                "route('secretariats.ods'",
            ],
        );

        $this->assertFileContainsAll(
            base_path('tests/Feature/Authorization/ServiceOrderAuthorizationTest.php'),
            [
                'Livewire::actingAs(',
                'assertSee(',
                'A categoria selecionada nao pertence a esta secretaria.',
                'assertDatabaseMissing(',
            ],
        );
    }

    public function test_policies_exist_for_protected_entities(): void
    {
        $expectations = [
            app_path('Models/Category.php') => app_path('Policies/CategoryPolicy.php'),
            app_path('Models/Secretariat.php') => app_path('Policies/SecretariatPolicy.php'),
            app_path('Models/ServiceOrder.php') => app_path('Policies/ServiceOrderPolicy.php'),
        ];

        foreach ($expectations as $model => $policy) {
            $this->assertFileExists($model);
            $this->assertFileExists($policy);
        }
    }

    /**
     * @return list<string>
     */
    private function phpFilesIn(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if (str_ends_with($path, '.php')) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param  list<string>  $forbiddenFragments
     */
    private function assertFileDoesNotContainAny(string $file, array $forbiddenFragments): void
    {
        $contents = file_get_contents($file);

        $this->assertIsString($contents);

        foreach ($forbiddenFragments as $fragment) {
            $this->assertStringNotContainsString($fragment, $contents, "{$file} contains {$fragment}");
        }
    }

    /**
     * @param  list<string>  $expectedFragments
     */
    private function assertFileContainsAll(string $file, array $expectedFragments): void
    {
        $contents = file_get_contents($file);

        $this->assertIsString($contents);

        foreach ($expectedFragments as $fragment) {
            $this->assertStringContainsString($fragment, $contents, "{$file} is missing {$fragment}");
        }
    }
}
