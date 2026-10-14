<?php

use App\Database\Migrations\AddSingleUseCoupons;
use App\Database\Migrations\AddCouponPhase;
use App\Services\Admin\CouponService;
use App\Services\PaymentService;
use App\Services\PricingService;
use CodeIgniter\Test\CIUnitTestCase;

final class SingleUseCouponTest extends CIUnitTestCase
{
    private PaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect();
        $schemas = [
            'comp_coupons' => 'coupon_id INTEGER PRIMARY KEY AUTOINCREMENT, coupon_code VARCHAR(25), coupon_type VARCHAR(20), coupon_amount INTEGER, coupon_comp INTEGER, coupon_start_date DATETIME, coupon_end_date DATETIME',
            'comp_entries' => 'entry_id VARCHAR(35) PRIMARY KEY, user_id INTEGER, comp_id INTEGER, series VARCHAR(3)',
            'comp_users' => 'user_id INTEGER PRIMARY KEY, user_type_id INTEGER',
            'comp_entry_payments' => 'payment_id INTEGER PRIMARY KEY AUTOINCREMENT, entry_id VARCHAR(35), payment_phase INTEGER, payment_total DECIMAL, coupon_code VARCHAR(25)',
        ];
        foreach ($schemas as $table => $schema) {
            $this->db->query('CREATE TABLE ' . $this->db->prefixTable($table) . ' (' . $schema . ')');
        }
        // Simulate a coupon that predates the migration.
        $this->db->table('comp_coupons')->insert($this->form('LEGACY'));
        require_once APPPATH . 'Database/Migrations/2026-09-18-000001_AddSingleUseCoupons.php';
        (new AddSingleUseCoupons())->up();
        require_once APPPATH . 'Database/Migrations/2026-09-18-000002_AddCouponPhase.php';
        (new AddCouponPhase())->up();
        $this->payments = new class extends PaymentService {
            public function getAddonItems(int $phase = 0): array { return []; }
            public function addonItemUser(string $entryId, int $addonId): array { return []; }
        };
        $pricing = $this->createMock(PricingService::class);
        $pricing->method('pricesFor')->willReturn(['regular' => 100, 'finalist' => 100, 'winner' => 100]);
        $pricing->method('currentRegTier')->willReturn('regular');
        $pricing->method('applyCoupon')->willReturn(['discount' => 100]);
        (new ReflectionProperty(PaymentService::class, 'pricing'))->setValue($this->payments, $pricing);
        $this->db->table('comp_users')->insert(['user_id' => 1, 'user_type_id' => 1]);
        foreach (['first', 'second'] as $entry) {
            $this->db->table('comp_entries')->insert(['entry_id' => $entry, 'user_id' => 1, 'comp_id' => 7, 'series' => 'No']);
            $this->payments->ensurePaymentRow($entry, 1);
        }
    }

    protected function tearDown(): void
    {
        foreach (['comp_coupons', 'comp_entry_payments', 'comp_entries', 'comp_users'] as $table) {
            $this->db->query('DROP TABLE ' . $this->db->prefixTable($table));
        }
        parent::tearDown();
    }

    private function form(string $code): array
    {
        return ['coupon_code' => $code, 'coupon_type' => 'Percentage', 'coupon_amount' => 100,
            'coupon_comp' => 0, 'coupon_start_date' => date('Y-m-d H:i:s', time() - 3600),
            'coupon_end_date' => date('Y-m-d H:i:s', time() + 3600)];
    }

    public function testExistingAndNewCouponsDefaultToMultiUse(): void
    {
        $legacy = $this->payments->getCoupon('LEGACY', 7)[0];
        $this->assertSame(0, (int)$legacy['coupon_single_use']);
        $id = (new CouponService())->createCoupon($this->form('NEW'));
        $this->assertGreaterThan(0, $id);
        $this->assertTrue($this->payments->reserveCoupon($id, 1));
        $this->assertTrue($this->payments->reserveCoupon($id, 2));
        $this->assertCount(1, $this->payments->getCoupon('NEW', 7));
    }

    public function testOneTimeCouponHasOnlyOneOwnerAndAllowsRetries(): void
    {
        $id = (new CouponService())->createCoupon($this->form('ONCE') + ['coupon_single_use' => '1']);
        $this->assertCount(1, $this->payments->getCoupon(' once ', 7));
        $this->assertTrue($this->payments->reserveCoupon($id, 1));
        $this->assertFalse($this->payments->reserveCoupon($id, 2));
        $this->assertTrue($this->payments->reserveCoupon($id, 1));
        $this->assertSame([], $this->payments->getCoupon('ONCE', 7));
        $this->assertSame([], $this->payments->getCoupon('ONCE', 7, 2));
        $this->assertCount(1, $this->payments->getCoupon('ONCE', 7, 1));
    }

    public function testCheckoutReservesZeroDollarCouponAndRejectsSecondCart(): void
    {
        (new CouponService())->createCoupon($this->form('FREE') + ['coupon_single_use' => '1']);
        foreach (['first', 'second'] as $entry) {
            $this->payments->updateCoupon($entry, '1', 'FREE');
            $this->assertEquals(0, $this->payments->calculateTotalsForEntry($entry, 1)['total']);
        }
        $this->assertEquals(0, $this->payments->calculateTotalsForEntry('first', 1, true)['total']);
        $this->assertEquals(0, $this->payments->calculateTotalsForEntry('first', 1, true)['total']);
        $this->expectException(RuntimeException::class);
        $this->payments->calculateTotalsForEntry('second', 1, true);
    }

    public function testEditingUsagePersistsWithoutResettingOwnership(): void
    {
        $service = new CouponService();
        $form = $this->form('EDIT');
        $id = $service->createCoupon($form);
        $this->assertTrue($service->updateCoupon($id, $form + ['coupon_single_use' => '1']));
        $this->assertTrue($this->payments->reserveCoupon($id, 1));
        $this->assertTrue($service->updateCoupon($id, $form + ['coupon_single_use' => '1']));
        $this->assertFalse($this->payments->reserveCoupon($id, 2));
    }

    public function testCompetitionAndExpiryStillApply(): void
    {
        $form = $this->form('SCOPED');
        $form['coupon_comp'] = 7;
        $service = new CouponService();
        $id = $service->createCoupon($form + ['coupon_single_use' => '1']);
        $this->assertCount(1, $this->payments->getCoupon('SCOPED', 7));
        $this->assertSame([], $this->payments->getCoupon('SCOPED', 8));
        $form['coupon_end_date'] = date('Y-m-d H:i:s', time() - 60);
        $service->updateCoupon($id, $form + ['coupon_single_use' => '1']);
        $this->assertSame([], $this->payments->getCoupon('SCOPED', 7));
    }

    public function testExistingAndNewCouponsDefaultToAllPhases(): void
    {
        $service = new CouponService();
        $id = $service->createCoupon($this->form('ALL'));
        $this->assertSame(0, $service->getCouponByID($id)->coupon_phase);
        foreach (['LEGACY', 'ALL'] as $code) {
            foreach ([1, 2, 3] as $phase) {
                $this->assertCount(1, $this->payments->getCoupon($code, 7, null, $phase));
            }
        }
    }

    public function testPhaseRestrictionsApplyInBothDirectionsAndCanBeEdited(): void
    {
        $service = new CouponService();
        $form = $this->form('PHASE');
        $id = $service->createCoupon($form + ['coupon_phase' => '1']);
        foreach ([1, 2] as $assignedPhase) {
            $this->assertTrue($service->updateCoupon($id, $form + ['coupon_phase' => (string)$assignedPhase]));
            $this->assertSame($assignedPhase, $service->getCouponByID($id)->coupon_phase);
            foreach ([1, 2, 3] as $paymentPhase) {
                $this->assertCount($assignedPhase === $paymentPhase ? 1 : 0,
                    $this->payments->getCoupon('PHASE', 7, null, $paymentPhase));
            }
        }
        $this->assertTrue($service->updateCoupon($id, $form + ['coupon_phase' => '0']));
        $this->assertCount(1, $this->payments->getCoupon('PHASE', 7, null, 1));
        $this->assertCount(1, $this->payments->getCoupon('PHASE', 7, null, 2));
    }

    public function testWrongPhaseCouponCannotDiscountCartOrBeReservedAtCheckout(): void
    {
        $service = new CouponService();
        foreach ([1, 2] as $assignedPhase) {
            $code = 'PHASE' . $assignedPhase;
            $id = $service->createCoupon($this->form($code) + ['coupon_phase' => (string)$assignedPhase, 'coupon_single_use' => '1']);
            $wrongPhase = $assignedPhase === 1 ? 2 : 1;
            $this->payments->updateCoupon('first', (string)$wrongPhase, $code);
            try {
                $this->payments->calculateTotalsForEntry('first', $wrongPhase, true);
                $this->fail('Checkout must reject a coupon for the wrong phase.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('payment phase', $e->getMessage());
            }
            $this->assertNull($service->getCouponByID($id)->coupon_payment_id);
            $cart = $this->payments->calculateTotalsForEntry('first', $wrongPhase);
            $this->assertEquals(100, $cart['total']);
            $this->assertEquals(0, $cart['discount']);
            $this->payments->updateCoupon('first', (string)$assignedPhase, $code);
            $this->assertEquals(0, $this->payments->calculateTotalsForEntry('first', $assignedPhase, true)['total']);
        }
    }

    public function testInvalidPhaseIsRejected(): void
    {
        $service = new CouponService();
        $id = $service->createCoupon($this->form('VALID'));
        $this->assertFalse($service->updateCoupon($id, $this->form('VALID') + ['coupon_phase' => '3']));
        $this->assertSame(0, $service->getCouponByID($id)->coupon_phase);
    }
}
