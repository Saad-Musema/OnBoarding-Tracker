<?php

use PHPUnit\Framework\TestCase;

class TestUserProgress extends TestCase {

    protected $user_id;

    // Creating a mock user
    protected function setUp(): void {
        $this->user_id = $this->create_mock_user();
    }

    // Clean up the test user
    protected function tearDown(): void {
        wp_delete_user($this->user_id, true); 
    }

    // Helper to create a mock user
    private function create_mock_user() {
        return wp_insert_user([
            'user_login' => 'testuser',
            'user_email' => 'testuser@example.com',
            'user_pass' => 'password'
        ]);
    }

    // Test initialization of onboarding metadata
    public function test_initialize_onboarding_metadata() {
        initialize_onboarding_metadata($this->user_id);

        $onboarding_data = get_user_meta($this->user_id, 'onboarding_progress', true);
        $this->assertIsArray($onboarding_data);
        $this->assertEquals(0, $onboarding_data['courses_checked']);
        $this->assertEquals(0, $onboarding_data['guidelines_checked']);
        $this->assertEquals(0, $onboarding_data['cv_submitted']);
        $this->assertEquals(0, $onboarding_data['mentor_chosen']);
    }

    // Test marking a step as completed
    public function test_update_onboarding_step() {
        initialize_onboarding_metadata($this->user_id);

        update_onboarding_step($this->user_id, 'courses_checked', 1);
        $onboarding_data = get_user_meta($this->user_id, 'onboarding_progress', true);

        $this->assertEquals(1, $onboarding_data['courses_checked']);
    }

    // Test CV submission
    public function test_cv_submission() {
        $cv_path = "/path/to/cvs/{$this->user_id}.pdf";
        file_put_contents($cv_path, "Test CV Content");

        $cv_exists = file_exists($cv_path);
        $this->assertTrue($cv_exists, "CV file should exist.");

        // Optional: Check that the file is properly linked in metadata
        update_user_meta($this->user_id, 'cv_path', $cv_path);
        $stored_path = get_user_meta($this->user_id, 'cv_path', true);
        $this->assertEquals($cv_path, $stored_path);
    }

    // Test mentor selection
    public function test_choose_mentor() {
        $mentor_id = 123; // Mock mentor ID
        update_onboarding_step($this->user_id, 'mentor_chosen', $mentor_id);

        $onboarding_data = get_user_meta($this->user_id, 'onboarding_progress', true);
        $this->assertEquals($mentor_id, $onboarding_data['mentor_chosen']);
    }

    // Test overall progress calculation
    public function test_overall_progress() {
        initialize_onboarding_metadata($this->user_id);

        // Complete two steps
        update_onboarding_step($this->user_id, 'courses_checked', 1);
        update_onboarding_step($this->user_id, 'cv_submitted', 1);

        $progress = get_onboarding_progress($this->user_id);
        $this->assertEquals(50, $progress, "Progress should be 50%.");
    }
}
