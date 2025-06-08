<?php
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    public function testDateConversion()
    {
        require_once __DIR__ . '/../backend/api.php';
        
        // Test valid date conversion
        $this->assertEquals('2024-06-15', convertDate('15/06/2024'));
        
        // Test invalid date format
        $this->assertFalse(convertDate('invalid-date'));
    }
    
    public function testAgeGroupDetermination()
    {
        require_once __DIR__ . '/../backend/api.php';
        
        // Test adult age
        $this->assertEquals('Adult', getAgeGroup(18));
        
        // Test child age
        $this->assertEquals('Child', getAgeGroup(10));
        
        // Test boundary case
        $this->assertEquals('Adult', getAgeGroup(12));
    }
    
    public function testInputValidation()
    {
        require_once __DIR__ . '/../backend/api.php';
        
        // Test valid input
        $validInput = [
            'Unit Name' => 'Test Unit 1',
            'Arrival' => '15/06/2024',
            'Departure' => '20/06/2024',
            'Occupants' => 2,
            'Ages' => [30, 28]
        ];
        $this->assertTrue(validateInput($validInput));
        
        // Test invalid input
        $invalidInput = [
            'Unit Name' => 'Test Unit 1',
            // Missing required fields
        ];
        $this->assertFalse(validateInput($invalidInput));
    }
} 