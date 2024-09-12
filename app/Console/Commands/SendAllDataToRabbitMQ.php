<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SourceData;
use App\Services\RabbitMQService;
use Exception;
use Illuminate\Console\Command;

class SendAllDataToRabbitMQ extends Command
{

    protected $signature = 'rabbitmq:send-all';

    protected $description = 'Send all data from source_data table to RabbitMQ';


    protected RabbitMQService $rabbitMQService;


    protected int $batchSize = 100;

    /**
     * Constructor to initialize RabbitMQService
     */
    public function __construct(RabbitMQService $rabbitMQService)
    {
        parent::__construct();
        $this->rabbitMQService = $rabbitMQService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $totalStartTime = microtime(true);
        $dbTime = 0;
        $rabbitMQTime = 0;
        $totalRecords = 0;

        // Process records in chunks to avoid memory overload
        SourceData::query()->orderBy('created_at')->chunk(1000, function ($records) use (&$totalRecords, &$dbTime, &$rabbitMQTime) {
            $dbStartTime = microtime(true);
            $recordArray = $records->toArray();
            $dbTime += microtime(true) - $dbStartTime;

            $rabbitMQStartTime = microtime(true);
            $this->rabbitMQService->publishMessagesInBatch('source_data_queue', $recordArray, $this->batchSize);
            $rabbitMQTime += microtime(true) - $rabbitMQStartTime;

            $totalRecords += count($recordArray);
        });

        $totalExecutionTime = microtime(true) - $totalStartTime;
        $this->info("Total records sent: {$totalRecords}");
        $this->info("Total execution time: " . round($totalExecutionTime, 2) . " seconds");
        $this->info("Database time: " . round($dbTime, 2) . " seconds");
        $this->info("RabbitMQ time: " . round($rabbitMQTime, 2) . " seconds");

        $this->rabbitMQService->close();

        return 0;
    }
}
