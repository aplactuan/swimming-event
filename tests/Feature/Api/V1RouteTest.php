<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class V1RouteTest extends TestCase
{
    public function test_unknown_api_v1_endpoints_return_json_not_found(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_unknown_user_api_endpoints_live_outside_v1_and_return_json(): void
    {
        $this->getJson('/api/user/does-not-exist')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
