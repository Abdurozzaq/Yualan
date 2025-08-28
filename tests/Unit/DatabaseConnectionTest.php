<?php
namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    /** @test */
    public function it_can_connect_to_the_database()
    {
        $connected = false;
        try {
            DB::connection()->getPdo();
            $connected = true;
        } catch (\Exception $e) {
            $connected = false;
        }
        $this->assertTrue($connected, 'Database connection failed.');
    }
}
