<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    protected function setUp(): void
    {
        // Suppress header modification warnings during tests
        @ob_start();
        require_once __DIR__ . '/../backend/helpers.php';
    }

    protected function tearDown(): void
    {
        @ob_end_clean();
    }

    public function testDateConversion()
    {
        $date = "2025-06-08";
        $expected = "08/06/2025";
        $this->assertEquals($expected, \convertDate($date));
    }

    public function testAgeGroupDetermination()
    {
        $age = 25;
        $this->assertEquals("Adult", \determineAgeGroup($age));
        
        $age = 11;
        $this->assertEquals("Child", \determineAgeGroup($age));
    }

    public function testInputValidation()
    {
        $validInput = [
            "arrival" => "2025-06-08",
            "departure" => "2025-06-10",
            "occupants" => 2,
            "ages" => [25, 24]
        ];
        
        $this->assertTrue(\validateBookingInput($validInput));
    }

    public function testAllowedOrigins()
    {
        $origins = \getAllowedOrigins();
        $this->assertIsArray($origins);
        $this->assertContains('https://gondwana-collection.com', $origins);
    }
} 