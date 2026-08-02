<?php

namespace Tests\Unit;

use App\Services\CreatorIntelligence\Import\CsvColumnMapper;
use App\Services\CreatorIntelligence\Import\Parsers\CurrencyParser;
use App\Services\CreatorIntelligence\Import\Parsers\DateTimeParser;
use App\Services\CreatorIntelligence\Import\Parsers\DurationParser;
use App\Services\CreatorIntelligence\Import\Parsers\IntegerMetricParser;
use App\Services\CreatorIntelligence\Import\Parsers\PercentageParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreatorIntelligenceImportParsersTest extends TestCase
{
    public function test_integer_percentage_currency_and_missing_values_are_normalized(): void
    {
        $this->assertSame(1234, (new IntegerMetricParser)->parse('1,234'));
        $this->assertSame(1234, (new IntegerMetricParser)->parse('1 234'));
        $this->assertSame('5.42', (new PercentageParser)->parse('5.42%'));
        $this->assertNull((new PercentageParser)->parse('N/A'));
        $this->assertSame('1234.56', (new CurrencyParser)->parse('USD $1,234.56'));
    }

    public function test_duration_and_timezone_parsing(): void
    {
        $parser = new DurationParser;
        $this->assertSame(125, $parser->parse('02:05'));
        $this->assertSame(3723, $parser->parse('01:02:03'));
        $this->assertSame(90, $parser->parse('PT1M30S'));
        $this->assertSame('2026-08-01T16:00:00+00:00', (new DateTimeParser)->parse('2026-08-01 12:00:00', 'America/New_York')->toIso8601String());
    }

    public function test_invalid_numeric_input_and_negative_percentages_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PercentageParser)->parse('-1%');
    }

    public function test_common_headers_are_automatically_mapped_without_ambiguous_subscribers(): void
    {
        $mapping = (new CsvColumnMapper)->automatic(["\xEF\xBB\xBFVideo", 'Video title', 'Watch time (hours)', 'Subscribers', 'Hype points']);
        $this->assertSame('platform_video_id', $mapping["\xEF\xBB\xBFVideo"]);
        $this->assertSame('title', $mapping['Video title']);
        $this->assertSame('watch_time_hours', $mapping['Watch time (hours)']);
        $this->assertArrayNotHasKey('Subscribers', $mapping);
        $this->assertSame('hype_points', $mapping['Hype points']);
    }
}
