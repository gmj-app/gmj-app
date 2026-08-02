<?php

namespace Database\Factories;

use App\Enums\ImportBatchSource;
use App\Enums\ImportBatchStatus;
use App\Models\CreatorChannel;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportBatch> */
class ImportBatchFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->uuid().'.csv';

        return ['creator_channel_id' => CreatorChannel::factory(), 'uploaded_by_user_id' => User::factory(), 'source' => ImportBatchSource::YouTubeStudio, 'original_filename' => 'analytics.csv', 'stored_filename' => $name, 'storage_disk' => 'local', 'storage_path' => 'creator-intelligence/imports/'.$name, 'snapshot_date' => now()->toDateString(), 'status' => ImportBatchStatus::Uploaded];
    }
}
