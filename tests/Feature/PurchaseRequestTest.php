<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Department;
use App\Models\Item;
use App\Models\Project;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_request_code_generation()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'Information Technology']);
        
        $pr = PurchaseRequest::create([
            'name' => 'Test Request',
            'requester_id' => $user->id,
            'department_id' => $dept->id,
            'request_date' => now(),
        ]);

        $this->assertNotNull($pr->code);
        $this->assertStringContainsString('#PR-', $pr->code);
        $this->assertStringContainsString('-' . date('Y') . '-', $pr->code);
        $this->assertStringContainsString('INF', $pr->code); // INF from Information Technology
    }

    public function test_purchase_request_totals_calculation()
    {
        $user = User::factory()->create();
        $tax = Tax::create(['name' => 'VAT', 'rate' => 15]);
        $itemModel = Item::create(['name' => 'Laptop', 'unit_price' => 1000]);
        
        $pr = PurchaseRequest::create([
            'name' => 'Total Calc Test',
            'requester_id' => $user->id,
            'request_date' => now(),
        ]);

        $prItem = new PurchaseRequestItem([
            'item_id' => $itemModel->id,
            'description' => 'Test Laptop',
            'quantity' => 2,
            'unit_price' => 1000,
            'tax_id' => $tax->id,
        ]);
        $prItem->purchase_request_id = $pr->id;
        $prItem->save();

        // Item level verification
        $this->assertEquals(2000, $prItem->subtotal);
        $this->assertEquals(300, $prItem->tax_value); // 15% of 2000
        $this->assertEquals(2300, $prItem->total);

        $pr->refresh();

        // Header level verification
        $this->assertEquals(2000, $pr->subtotal);
        $this->assertEquals(300, $pr->tax_amount);
        $this->assertEquals(2300, $pr->total_amount);
    }
}
