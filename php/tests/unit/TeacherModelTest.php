<?php

use App\Models\TeacherModel;
use PHPUnit\Framework\TestCase;

final class TeacherModelTest extends TestCase
{
    public function testBuildProfilePayloadUsesUserIdentity(): void
    {
        $user = [
            'id' => 42,
            'name' => 'Jane Doe',
            'email' => 'Jane.Doe@School.edu',
            'role' => 'teacher',
        ];

        $payload = TeacherModel::buildProfilePayload($user);

        $this->assertSame('T-042', $payload['employee_id']);
        $this->assertSame('Jane Doe', $payload['name']);
        $this->assertSame('jane.doe@school.edu', $payload['email']);
        $this->assertSame(42, $payload['user_id']);
        $this->assertSame('All Levels', $payload['grade_level']);
    }
}
