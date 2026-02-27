<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CertificateRequestHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('certificate_request');
    }

    public function testEmptyStatusDefaultsToPendingWhenRequested(): void
    {
        [$label, $class, $key] = certificate_request_status_pill('', true);

        $this->assertSame('Pending', $label);
        $this->assertSame('request-pending', $class);
        $this->assertSame('pending', $key);
    }

    public function testUnknownStatusUsesNotRequestedClass(): void
    {
        [$label, $class, $key] = certificate_request_status_pill('Needs Follow Up', false);

        $this->assertSame('Needs Follow Up', $label);
        $this->assertSame('request-not-requested', $class);
        $this->assertSame('needs-follow-up', $key);
    }

    public function testKnownStatusMapsToExpectedClass(): void
    {
        [$label, $class, $key] = certificate_request_status_pill('In Production', false);

        $this->assertSame('In Production', $label);
        $this->assertSame('request-in-production', $class);
        $this->assertSame('in-production', $key);
    }
}
