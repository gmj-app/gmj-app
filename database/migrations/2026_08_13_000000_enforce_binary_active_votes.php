<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'user_picks_request_active_index';

    private const CONSTRAINT = 'user_picks_active_vote_binary';

    public function up(): void
    {
        $audit = $this->auditActiveVotes();
        Log::info('Binary vote migration pre-normalization audit.', $audit);

        if ($audit['active_non_positive'] > 0 || $audit['active_null'] > 0) {
            throw new RuntimeException(sprintf(
                'Binary vote migration stopped: found %d active vote rows with vote_count <= 0 and %d with NULL vote_count. Repair these corrupt rows explicitly, then rerun the migration.',
                $audit['active_non_positive'],
                $audit['active_null'],
            ));
        }

        $normalized = DB::table('user_picks')
            ->whereNull('released_at')
            ->where('vote_count', '>', 1)
            ->update(['vote_count' => 1]);

        $remainingViolations = DB::table('user_picks')
            ->whereNull('released_at')
            ->where(fn ($query) => $query->whereNull('vote_count')->orWhere('vote_count', '!=', 1))
            ->count();

        if ($remainingViolations > 0) {
            throw new RuntimeException("Binary vote normalization failed validation: {$remainingViolations} active rows still violate vote_count = 1.");
        }

        Log::info('Binary vote migration normalization complete.', [
            'normalized_active_rows' => $normalized,
            'active_support_total_after' => $audit['active_total'],
            'released_rows_preserved' => $audit['released_total'],
        ]);

        $this->ensureIndex();
        $this->ensureConstraint();
    }

    public function down(): void
    {
        $this->dropConstraintIfPresent();

        if (Schema::hasIndex('user_picks', self::INDEX)) {
            Schema::table('user_picks', fn (Blueprint $table) => $table->dropIndex(self::INDEX));
        }
    }

    /** @return array<string, int> */
    private function auditActiveVotes(): array
    {
        $active = DB::table('user_picks')->whereNull('released_at');

        return [
            'active_total' => (clone $active)->count(),
            'active_at_one' => (clone $active)->where('vote_count', 1)->count(),
            'active_above_one' => (clone $active)->where('vote_count', '>', 1)->count(),
            'active_non_positive' => (clone $active)->whereNotNull('vote_count')->where('vote_count', '<=', 0)->count(),
            'active_null' => (clone $active)->whereNull('vote_count')->count(),
            'weighted_active_total_before' => (int) (clone $active)->where('vote_count', '>', 0)->sum('vote_count'),
            'unique_active_support_total_after' => (clone $active)->where('vote_count', '>', 0)->count(),
            'released_total' => DB::table('user_picks')->whereNotNull('released_at')->count(),
        ];
    }

    private function ensureConstraint(): void
    {
        $driver = DB::getDriverName();
        $definition = $this->constraintDefinition();

        if ($definition !== null) {
            $normalized = strtolower(preg_replace('/[`"\s()]+/', '', $definition) ?? '');
            $correctCheck = str_contains($normalized, 'released_atisnotnullorvote_count=1');
            $correctSqliteTrigger = str_contains($normalized, 'new.released_atisnullandnew.vote_countisnot1');
            if (! $correctCheck && ! $correctSqliteTrigger) {
                throw new RuntimeException('Existing '.self::CONSTRAINT." has an unexpected definition: {$definition}");
            }

            return;
        }

        match ($driver) {
            'mysql' => DB::statement('ALTER TABLE user_picks ADD CONSTRAINT '.self::CONSTRAINT.' CHECK (released_at IS NOT NULL OR vote_count = 1)'),
            'pgsql' => DB::statement('ALTER TABLE user_picks ADD CONSTRAINT '.self::CONSTRAINT.' CHECK (released_at IS NOT NULL OR vote_count = 1)'),
            'sqlite' => DB::unprepared('CREATE TRIGGER '.self::CONSTRAINT."_insert BEFORE INSERT ON user_picks WHEN NEW.released_at IS NULL AND NEW.vote_count IS NOT 1 BEGIN SELECT RAISE(ABORT, 'active vote_count must equal 1'); END; CREATE TRIGGER ".self::CONSTRAINT."_update BEFORE UPDATE ON user_picks WHEN NEW.released_at IS NULL AND NEW.vote_count IS NOT 1 BEGIN SELECT RAISE(ABORT, 'active vote_count must equal 1'); END;"),
            default => throw new RuntimeException("Binary vote constraint is unsupported for database driver {$driver}."),
        };
    }

    private function ensureIndex(): void
    {
        $index = collect(Schema::getIndexes('user_picks'))
            ->first(fn (array $index): bool => ($index['name'] ?? null) === self::INDEX);

        if ($index !== null) {
            if (array_values($index['columns'] ?? []) !== ['recommendation_id', 'released_at']) {
                throw new RuntimeException('Existing '.self::INDEX.' has unexpected columns: '.implode(', ', $index['columns'] ?? []));
            }

            return;
        }

        Schema::table('user_picks', function (Blueprint $table): void {
            $table->index(['recommendation_id', 'released_at'], self::INDEX);
        });
    }

    private function constraintDefinition(): ?string
    {
        return match (DB::getDriverName()) {
            'mysql' => DB::table('information_schema.check_constraints')
                ->where('constraint_schema', DB::getDatabaseName())
                ->where('constraint_name', self::CONSTRAINT)
                ->value('check_clause'),
            'pgsql' => DB::table('pg_constraint')->where('conname', self::CONSTRAINT)->value(DB::raw('pg_get_constraintdef(oid)')),
            'sqlite' => $this->sqliteConstraintDefinition(),
            default => null,
        };
    }

    private function sqliteConstraintDefinition(): ?string
    {
        $definitions = DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->whereIn('name', [self::CONSTRAINT.'_insert', self::CONSTRAINT.'_update'])
            ->orderBy('name')
            ->pluck('sql');

        if ($definitions->isEmpty()) {
            return null;
        }

        if ($definitions->count() !== 2) {
            throw new RuntimeException('Existing SQLite binary-vote enforcement is incomplete; expected both insert and update triggers.');
        }

        return $definitions->implode("\n");
    }

    private function dropConstraintIfPresent(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS '.self::CONSTRAINT.'_insert; DROP TRIGGER IF EXISTS '.self::CONSTRAINT.'_update;');

            return;
        }

        if ($this->constraintDefinition() === null) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE user_picks DROP CHECK '.self::CONSTRAINT),
            'pgsql' => DB::statement('ALTER TABLE user_picks DROP CONSTRAINT '.self::CONSTRAINT),
            default => null,
        };
    }
};
