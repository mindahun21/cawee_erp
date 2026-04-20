<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Services\AI\Tools\LiveDataTool;
use App\Services\AI\Data\RecruitmentDataPreloader;
use App\Services\AI\Data\ProcurementDataPreloader;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LiveDataToolTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LiveDataTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['view_recruitment', 'view_procurement']);
        
        $this->actingAs($this->user);
        
        $this->tool = new LiveDataTool();
    }

    /** @test */
    public function it_can_fetch_recruitment_data_without_filters()
    {
        $result = $this->tool->execute('recruitment', []);
        
        $this->assertStringContainsString('LIVE RECRUITMENT DATA SNAPSHOT', $result);
        $this->assertStringContainsString('## Plans', $result);
        $this->assertStringContainsString('## Campaigns', $result);
        $this->assertStringContainsString('## Applications', $result);
    }

    /** @test */
    public function it_can_fetch_procurement_data_without_filters()
    {
        $result = $this->tool->execute('procurement', []);
        
        $this->assertStringContainsString('LIVE PROCUREMENT DATA SNAPSHOT', $result);
        $this->assertStringContainsString('## Requisitions', $result);
        $this->assertStringContainsString('## Purchase Orders', $result);
        $this->assertStringContainsString('## Tenders', $result);
    }

    /** @test */
    public function it_can_fetch_recruitment_data_with_status_filter()
    {
        $result = $this->tool->execute('recruitment', [
            'status' => 'active',
            'limit' => 5,
        ]);
        
        $this->assertStringContainsString('LIVE RECRUITMENT DATA SNAPSHOT', $result);
    }

    /** @test */
    public function it_can_fetch_procurement_data_with_date_range_filter()
    {
        $result = $this->tool->execute('procurement', [
            'date_range' => 'last_30_days',
            'limit' => 10,
        ]);
        
        $this->assertStringContainsString('LIVE PROCUREMENT DATA SNAPSHOT', $result);
    }

    /** @test */
    public function it_returns_error_for_invalid_module()
    {
        $result = $this->tool->execute('invalid_module', []);
        
        $this->assertStringContainsString('Error: Module', $result);
        $this->assertStringContainsString('not available', $result);
    }

    /** @test */
    public function it_returns_error_when_user_lacks_permission()
    {
        // Create user without permissions
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);
        
        $result = $this->tool->execute('recruitment', []);
        
        $this->assertStringContainsString('Error: You do not have permission', $result);
    }

    /** @test */
    public function it_converts_to_prism_tool_correctly()
    {
        $prismTool = $this->tool->asPrismTool();
        
        // Prism Tool is returned correctly
        $this->assertNotNull($prismTool);
    }

    /** @test */
    public function recruitment_preloader_can_access_checks_permission()
    {
        $preloader = new RecruitmentDataPreloader();
        
        $this->assertTrue($preloader->canAccess($this->user));
        
        // User without permission
        $userWithoutPermission = User::factory()->create();
        $this->assertFalse($preloader->canAccess($userWithoutPermission));
    }

    /** @test */
    public function procurement_preloader_can_access_checks_permission()
    {
        $preloader = new ProcurementDataPreloader();
        
        $this->assertTrue($preloader->canAccess($this->user));
        
        // User without permission
        $userWithoutPermission = User::factory()->create();
        $this->assertFalse($preloader->canAccess($userWithoutPermission));
    }
}
