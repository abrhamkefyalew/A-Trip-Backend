<?php

namespace Database\Seeders;

use App\Models\Constant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
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
            //     'percent_value' => 1,
            // ],
            // [
            //     'title' => Constant::OTHER_CONSTANT_TWO,
            //     'percent_value' => 1,
            // ],
            // [
            //     'title' => Constant::OTHER_CONSTANT_THREE,
            //     'percent_value' => 1,
            // ],
        ];

        foreach ($constants as $constantData) {
            $validator = Validator::make($constantData, Constant::$rules, Constant::$messages);

            if ($validator->fails()) {
                // Handle validation errors here (e.g., log errors, throw exception)
                throw new \Exception($validator->errors()->first());
            }

            // if you use this (i.e this "updateOrCreate"),    COMMENT/remove the below "upsert" (i.e the "upsert" outside of the foreach) 
            // Constant::updateOrCreate(['title' => $constantData['title']], $constantData);
        }

        // if you this (i.e this "upsert"),    COMMENT/remove the above updateOrCreate (i.e the "updateOrCreate" inside of the foreach)
        Constant::upsert($constants, ['title']);
    }
}
