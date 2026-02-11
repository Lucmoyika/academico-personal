<?php

namespace Tests\Unit;

use App\Traits\UsernameTrait;
use Tests\TestCase;

class UsernameTraitTest extends TestCase
{
    use UsernameTrait;

    public function test_two_word_name(): void
    {
        $username = $this->generateUsername('John Smith');

        // Last 2 words: "john" → "joh", "smith" → "smith" (up to 8 chars)
        $this->assertStringStartsWith('johsmith', $username);
    }

    public function test_three_word_name(): void
    {
        $username = $this->generateUsername('John Michael Smith');

        // Last 2 words: "michael" → "mic", "smith" → "smith"
        $this->assertStringStartsWith('micsmith', $username);
    }

    public function test_single_word_name(): void
    {
        $username = $this->generateUsername('Madonna');

        // Only 1 word: "madonna" → "mad", part2 empty
        $this->assertStringStartsWith('mad', $username);
    }

    public function test_random_part_in_range(): void
    {
        $username = $this->generateUsername('John Smith');

        // Extract the numeric suffix
        $numericPart = (int) substr($username, strlen('johsmith'));

        $this->assertGreaterThanOrEqual(999, $numericPart);
        $this->assertLessThanOrEqual(9999, $numericPart);
    }
}
