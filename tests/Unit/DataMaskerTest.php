<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\DataMasker\DataMasker;
use Concept\Extensions\DataMasker\RegexDataMaskerRule;
use PHPUnit\Framework\TestCase;

final class DataMaskerTest extends TestCase
{
    public function testMaskReturnsDataUnchangedWhenNoRules(): void
    {
        $masker = new DataMasker();
        $data = ['user' => 'alice', 'password' => 'secret'];

        $this->assertSame($data, $masker->mask($data));
    }

    public function testMaskReplacesSensitiveKeysInArray(): void
    {
        $masker = new DataMasker();
        $masker->addRule(new RegexDataMaskerRule(keyPatterns: ['/password/i', '/token/i']));

        $result = $masker->mask([
            'user' => 'alice',
            'password' => 'secret',
            'api_token' => 'abc123',
        ]);

        $this->assertSame('alice', $result['user']);
        $this->assertSame(DataMasker::MASK_CHARS, $result['password']);
        $this->assertSame(DataMasker::MASK_CHARS, $result['api_token']);
    }

    public function testMaskRecursesIntoNestedArrays(): void
    {
        $masker = new DataMasker();
        $masker->addRule(new RegexDataMaskerRule(keyPatterns: ['/password/i']));

        $result = $masker->mask([
            'profile' => [
                'name' => 'alice',
                'password' => 'secret',
            ],
        ]);

        $this->assertSame('alice', $result['profile']['name']);
        $this->assertSame(DataMasker::MASK_CHARS, $result['profile']['password']);
    }

    public function testMaskReplacesSensitiveObjectProperties(): void
    {
        $masker = new DataMasker();
        $masker->addRule(new RegexDataMaskerRule(keyPatterns: ['/password/i']));

        $payload = new SensitivePayload('alice', 'secret');
        $result = $masker->mask($payload);

        $this->assertInstanceOf(SensitivePayload::class, $result);
        $this->assertSame('alice', $result->user);
        $this->assertSame(DataMasker::MASK_CHARS, $result->password);
    }

    public function testMaskAppliesRegexRulesToStringValues(): void
    {
        $masker = new DataMasker();
        $masker->addRule(new RegexDataMaskerRule(patterns: [
            '/secret-\d+/' => '[redacted]',
        ]));

        $result = $masker->mask('value secret-123 trailing');

        $this->assertSame('value [redacted] trailing', $result);
    }

    public function testClearRulesDisablesMasking(): void
    {
        $masker = new DataMasker();
        $masker->addRule(new RegexDataMaskerRule(keyPatterns: ['/password/i']));
        $masker->clearRules();

        $data = ['password' => 'secret'];

        $this->assertSame($data, $masker->mask($data));
    }
}

final class SensitivePayload
{
    public function __construct(
        public string $user,
        public string $password,
    ) {}
}
