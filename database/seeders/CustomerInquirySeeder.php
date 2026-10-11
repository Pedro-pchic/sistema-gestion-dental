<?php

namespace Database\Seeders;

use App\Models\CustomerInquiry;
use Illuminate\Database\Seeder;

class CustomerInquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerInquiry::factory()->count(10)->create();
    }
}
