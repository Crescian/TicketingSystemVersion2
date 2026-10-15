<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SlaRuleController;
use App\Http\Controllers\Admin\WorkloadClassController;
use App\Models\SlaCategory;
use App\Models\SlaRule;
use App\Models\WorkloadClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// An SLA rule can carry a default workload class: it's saved with the rule, and
// editing the class pushes its response/resolution minutes onto every linked rule.
// The project's migrations are Postgres-specific, so only the tables touched here
// are built (same approach as TicketHoldSlaTest), and the controllers are called
// directly rather than through the role-gated portal routes.
class SlaRuleWorkloadClassTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('workload_classes', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('typical_application')->nullable();
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes')->nullable();
            $table->string('response_label');
            $table->string('resolution_label');
            $table->boolean('requires_manual_resolution')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sla_categories', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sla_rules', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('sla_category_id');
            $table->string('subcategory_name');
            $table->string('priority');
            $table->uuid('workload_class_id')->nullable();
            $table->decimal('response_time_minutes', 8, 1);
            $table->decimal('resolution_time_minutes', 8, 1);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->boolean('helpdesk_resolvable')->default(false);
            $table->boolean('admin_only')->default(false);
            $table->timestamps();
        });
    }

    private function workloadClass(array $overrides = []): WorkloadClass
    {
        return WorkloadClass::create($overrides + [
            'name' => 'Standard',
            'response_minutes' => 30,
            'resolution_minutes' => 480,
            'response_label' => 'Within 30 Minutes',
            'resolution_label' => 'Within 1 Business Day',
            'requires_manual_resolution' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function rule(WorkloadClass $wc, float $response, float $resolution): SlaRule
    {
        $cat = SlaCategory::firstOrCreate(['name' => 'Hardware'], ['is_active' => true, 'sort_order' => 1]);

        return SlaRule::create([
            'sla_category_id' => $cat->id,
            'subcategory_name' => 'Laptop',
            'priority' => 'Medium',
            'workload_class_id' => $wc->id,
            'response_time_minutes' => $response,
            'resolution_time_minutes' => $resolution,
            'is_active' => true,
        ]);
    }

    private function updateClass(WorkloadClass $wc, array $fields): void
    {
        $request = Request::create('/', 'PUT', $fields + [
            'name' => $wc->name,
            'response_label' => $wc->response_label,
            'resolution_label' => $wc->resolution_label,
        ]);

        app(WorkloadClassController::class)->update($request, $wc);
    }

    public function test_rule_is_saved_with_its_workload_class(): void
    {
        $wc = $this->workloadClass();
        $cat = SlaCategory::create(['name' => 'Hardware', 'is_active' => true, 'sort_order' => 1]);

        app(SlaRuleController::class)->storeRule(Request::create('/', 'POST', [
            'sla_category_id' => $cat->id,
            'subcategory_name' => 'Laptop',
            'priority' => 'Medium',
            'workload_class_id' => $wc->id,
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 480,
        ]));

        $rule = SlaRule::firstOrFail();
        $this->assertSame($wc->id, $rule->workload_class_id);
        $this->assertSame('Standard', $rule->workloadClass->name);
    }

    public function test_editing_the_class_syncs_linked_rule_minutes(): void
    {
        $wc = $this->workloadClass();
        $rule = $this->rule($wc, 30, 480);

        $this->updateClass($wc, ['response_minutes' => 60, 'resolution_minutes' => 960]);

        $rule->refresh();
        $this->assertSame(60.0, $rule->response_time_minutes);
        $this->assertSame(960.0, $rule->resolution_time_minutes);
    }

    public function test_manual_resolution_class_only_syncs_response_and_skips_invalid_rules(): void
    {
        $wc = $this->workloadClass(['name' => 'Vendor Dependent', 'resolution_minutes' => null, 'requires_manual_resolution' => true]);
        $kept = $this->rule($wc, 30, 2880);
        $tooShort = $this->rule($wc, 30, 90);

        $this->updateClass($wc, ['response_minutes' => 120, 'requires_manual_resolution' => 1]);

        $kept->refresh();
        $this->assertSame(120.0, $kept->response_time_minutes);
        $this->assertSame(2880.0, $kept->resolution_time_minutes);

        // 120m response would not be under its 90m resolution — left as it was.
        $tooShort->refresh();
        $this->assertSame(30.0, $tooShort->response_time_minutes);
        $this->assertSame(90.0, $tooShort->resolution_time_minutes);
    }
}
