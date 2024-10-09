<?php

namespace Database\Seeders;

use App\Models\Constant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ConstantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $constants = [
            [
                'title' => Constant::ORDER_USER_INITIAL_PAYMENT_PERCENT,
                'percent_value' => 25,
            ],
            // [
            //     'title' => Constant::OTHER_CONSTANT_ONE,
            //     'percent_value' => 10,
            // ],
            // [
            //     'title' => Constant::OTHER_CONSTANT_TWO,
            //     'percent_value' => 5,
            // ],
            // [
            //     'title' => Constant::OTHER_CONSTANT_THREE,
            //     'percent_value' => 1,
            // ],
        ];

        Constant::upsert($constants, ['title']);
    }
}
