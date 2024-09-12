<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RabbitMQService;
use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAndSendData extends Command
{
    protected $signature = 'data:generate-send';
    protected $description = 'Generate new data, store in DB and send to RabbitMQ';
    protected RabbitMQService $rabbitMQService;
    const int GENERATE_INTERVAL = 2; // seconds

    public function __construct(RabbitMQService $rabbitMQService)
    {
        parent::__construct();
        $this->rabbitMQService = $rabbitMQService;
    }

    public function handle(): void
    {
        $faker = Faker::create();
        $data = [];
        $batchSize = 1000;

        while (true) {
            // Generate data
            for ($i = 0; $i < $batchSize; $i++) {
                $data[] = [
                    'name' => $faker->name,
                    'description' => $faker->text,
                    'created_at' => now(),
                ];
            }

            // Start transaction
            DB::beginTransaction();
            try {
                // Store data in the database
                DB::table('source_data')->insert($data);
                $this->info("Batch of $batchSize records stored in DB.");
                DB::commit();

                // Send data to RabbitMQ
                $this->rabbitMQService->publishMessagesInBatch('source_data_queue', $data, $batchSize);
                $this->info("Batch of $batchSize records sent to RabbitMQ.");
            } catch (\Exception $e) {
                // Rollback transaction in case of error
                DB::rollBack();
                $this->error("Failed to store data in DB: " . $e->getMessage());
            }

            $data = [];
            sleep(self::GENERATE_INTERVAL);
        }
    }
}
