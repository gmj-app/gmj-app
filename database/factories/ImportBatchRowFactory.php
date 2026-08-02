<?php

namespace Database\Factories;

use App\Enums\ImportRowStatus;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportBatchRow> */
class ImportBatchRowFactory extends Factory
{
    public function definition(): array
    {
        return ['import_batch_id' => ImportBatch::factory(), 'row_number' => fake()->unique()->numberBetween(2, 10000), 'raw_data' => ['Video title' => fake()->sentence()], 'status' => ImportRowStatus::Pending];
    }
}
