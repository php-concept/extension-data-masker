<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\DataMasker\RegexDataMaskerRule;
use PHPUnit\Framework\TestCase;

final class RegexDataMaskerRuleTest extends TestCase
{
    public function testIsSensitiveKeyMatchesConfiguredPatterns(): void
    {
        $rule = new RegexDataMaskerRule(keyPatterns: ['/password/i', '/^token$/']);

        $this->assertTrue($rule->isSensitiveKey('password'));
        $this->assertTrue($rule->isSensitiveKey('user_password'));
        $this->assertTrue($rule->isSensitiveKey('token'));
        $this->assertFalse($rule->isSensitiveKey('username'));
        $this->assertFalse($rule->isSensitiveKey('api_token'));
    }

    public function testApplyReplacesMatchingPatterns(): void
    {
        $rule = new RegexDataMaskerRule(patterns: [
            '/\b\d{4}-\d{4}-\d{4}-\d{4}\b/' => '[card]',
            '/Bearer\s+\S+/' => 'Bearer [token]',
        ]);

        $value = 'card 4111-1111-1111-1111 auth Bearer abc.def.ghi';

        $this->assertSame('card [card] auth Bearer [token]', $rule->apply($value));
    }

    public function testApplyMasksCardNumberKeepingFirstAndLastGroups(): void
    {
        $rule = new RegexDataMaskerRule(patterns: [
            '/(\d{4})-\d{4}-\d{4}-(\d{4})/' => '$1-****-****-$2',
        ]);

        $this->assertSame('4111-****-****-1111', $rule->apply('4111-1111-1111-1111'));
    }

    public function testApplyMasksCardNumberKeepingFirstAndLastGroupsWithText(): void
    {
        $rule = new RegexDataMaskerRule(patterns: [
            '/(\d{4})-\d{4}-\d{4}-(\d{4})/' => '$1-****-****-$2',
        ]);

        $this->assertSame(
            'card 4111-****-****-1111 auth Bearer abc.def.ghi',
            $rule->apply('card 4111-1111-1111-1111 auth Bearer abc.def.ghi')
        );
    }

    public function testApplyReturnsOriginalValueWhenNothingMatches(): void
    {
        $rule = new RegexDataMaskerRule(patterns: [
            '/missing/' => 'x',
        ]);

        $this->assertSame('plain text', $rule->apply('plain text'));
    }
}
