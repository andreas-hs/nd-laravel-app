<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SourceDataSeeder extends Seeder
{
    private const BATCH_SIZE = 1000;

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $faker = Faker::create();
        $data = [];

        $startTime = microtime(true);
        DB::beginTransaction();

        try {
            foreach (range(1, 10000) as $index) {
                $data[] = [
                    'name' => $faker->name,
                    'description' => $faker->text,
                    'created_at' => now(),
                ];

                if (count($data) >= self::BATCH_SIZE) {
                    DB::table('source_data')->insert($data);
                    $data = [];
                }
            }

            if (!empty($data)) {
                DB::table('source_data')->insert($data);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        echo "Execution time: " . $executionTime . " seconds\n";
    }
}
