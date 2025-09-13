<?php

namespace App\Services\Api\V1\Filters;

use Illuminate\Database\Eloquent\Builder;

class OrderFilterService
{

    /**
     * Applies filters to the AssetMain query based on request input.
     *
     * @param  Builder  $builder
     * @param  array    $filters
     * @return Builder
     */
    public static function applyOrderFilter(Builder $builder, array $filters): Builder
    {
        return $builder
            ->when(isset($filters['organization_id_search']) && filled($filters['organization_id_search']), fn($q) =>
                $q->where('organization_id', $filters['organization_id_search']))
            ->when(isset($filters['supplier_id_search']) && filled($filters['supplier_id_search']), fn($q) =>
                $q->where('supplier_id', $filters['supplier_id_search']))
            ->when(isset($filters['driver_id_search']) && filled($filters['driver_id_search']), fn($q) =>
                $q->where('driver_id', $filters['driver_id_search']))
            ->when(isset($filters['vehicle_id_search']) && filled($filters['vehicle_id_search']), fn($q) =>
                $q->where('vehicle_id', $filters['vehicle_id_search']))
            ->when(isset($filters['order_code_search']) && filled($filters['order_code_search']), fn($q) =>
                $q->where('order_code', $filters['order_code_search']))
            ->when(isset($filters['is_terminated_search']) && filled($filters['is_terminated_search']), fn($q) =>
                $q->where('is_terminated', $filters['is_terminated_search']))
            ->when(isset($filters['order_description_search']) && filled($filters['order_description_search']), fn($q) =>
                $q->where('order_description', 'like', '%' . $filters['order_description_search'] . '%'))
            ->when(isset($filters['order_status_search']) && filled($filters['order_status_search']), function ($q) use ($filters) {
                $statusSearch = $filters['order_status_search'];



                // OPTIONAL // since is it checked below also // may be redundant // use this if you want to return error for wrong values sent during filter
                // if (!in_array($statusSearch, \App\Models\Order::allowedOrderStatuses(), true)) {
                //     abort(400, 'Invalid value for order_status_search');
                // }

                // allowed values are set in the Model
                if (in_array($statusSearch, \App\Models\Order::allowedOrderStatuses() /*, true */ )) {  // 'true' - -   -   -   -   -   - => checks BOTH "VALUE" - & - "TYPE" must match. (i.e. compared using '===')   -> # STRICT COMPARISON
                                                                                                        // 'if false  - or - 'NOT set'  - => checks ONLY "VALUE" -  -   -   -   -   -   - (i.e. compared using '==')    -> # LOOSE COMPARISON
                    $q->where('status', $statusSearch);
                }
            })
            ->when(array_key_exists('pr_status_search', $filters), function ($q) use ($filters) {
                $prStatusSearch = $filters['pr_status_search'];

                // allowed values are set in here
                $allowedPrStatuses = [
                    null,
                    \App\Models\Order::ORDER_PR_STARTED,
                    \App\Models\Order::ORDER_PR_LAST,
                    \App\Models\Order::ORDER_PR_COMPLETED,
                    \App\Models\Order::ORDER_PR_TERMINATED,
                ];



                // OPTIONAL // since is it checked below also // may be redundant // use this if you want to return error for wrong values sent during filter
                // if (!in_array($prStatusSearch, $allowedPrStatuses, true)) {
                //     abort(400, 'Invalid value for pr_status_search');
                // }

                if (in_array($prStatusSearch, $allowedPrStatuses, true)) {  // 'true' - -   -   -   -   -   - => checks BOTH "VALUE" - & - "TYPE" must match. (i.e. compared using '===')   -> # STRICT COMPARISON
                                                                            // 'if false  - or - 'NOT set'  - => checks ONLY "VALUE" -  -   -   -   -   -   - (i.e. compared using '==')    -> # LOOSE COMPARISON
                    if (is_null($prStatusSearch)) {
                        $q->whereNull('pr_status');
                    } else {
                        $q->where('pr_status', $prStatusSearch);
                    }
                }
            });
    }
}



// NOT USED
// namespace App\Services\Api\V1\Filters;

// use Illuminate\Database\Eloquent\Builder;

// class OrderFilterService
// {
//     public static function applyOrderFilter(Builder $builder, array $filters): Builder
//     {
//         return $builder
//             ->when(filled($filters['organization_id_search']), fn($q) =>
//                 $q->where('organization_id', $filters['organization_id_search']))
//             ->when(filled($filters['supplier_id_search']), fn($q) =>
//                 $q->where('supplier_id', $filters['supplier_id_search']))
//             ->when(filled($filters['driver_id_search']), fn($q) =>
//                 $q->where('driver_id', $filters['driver_id_search']))
//             ->when(filled($filters['vehicle_id_search']), fn($q) =>
//                 $q->where('vehicle_id', $filters['vehicle_id_search']))
//             ->when(filled($filters['order_code_search']), fn($q) =>
//                 $q->where('order_code', $filters['order_code_search']))
//             ->when(filled($filters['is_terminated_search']), fn($q) =>
//                 $q->where('is_terminated', $filters['is_terminated_search']))
//             ->when(filled($filters['order_description_search']), fn($q) =>
//                 $q->where('order_description', 'like', '%' . $filters['order_description_search'] . '%'))
//             ->when(filled($filters['order_status_search']), function ($q) use ($filters) {
//                 $status = $filters['order_status_search'];
                
//                 // allowed values are set in the Model
//                 if (in_array($status, \App\Models\Order::allowedOrderStatuses() /*, true*/ )) {    // 'true' - -   -   -   -   -   - => checks BOTH "VALUE" - & - "TYPE" must match. (i.e. compared using '===')   -> # STRICT COMPARISON
//                                                                                                    // 'if false  - or - 'NOT set'  - => checks ONLY "VALUE" -  -   -   -   -   -   - (i.e. compared using '==')    -> # LOOSE COMPARISON
//                     $q->where('status', $status);
//                 }

//             })
//             ->when(array_key_exists('pr_status_search', $filters), function ($q) use ($filters) {
//                 $status = $filters['pr_status_search'];

//                 // allowed values are set in here
//                 $allowedPrStatuses = [
//                     null,
//                     \App\Models\Order::ORDER_PR_STARTED,
//                     \App\Models\Order::ORDER_PR_LAST,
//                     \App\Models\Order::ORDER_PR_COMPLETED,
//                     \App\Models\Order::ORDER_PR_TERMINATED,
//                 ];

//                 if (in_array($status, $allowedPrStatuses, true)) {  // 'true' - -   -   -   -   -   - => checks BOTH "VALUE" - & - "TYPE" must match. (i.e. compared using '===')   -> # STRICT COMPARISON
//                                                                     // 'if false  - or - 'NOT set'  - => checks ONLY "VALUE" -  -   -   -   -   -   - (i.e. compared using '==')    -> # LOOSE COMPARISON
//                     $q->where('pr_status', $status);
//                 }

//             });
//     }
// }


// 
/*
    //     ### The `'true'` in `in_array($status, $allowed, true)`

    //         This third parameter in the `in_array()` function is the **strict mode**:

    //             * When set to `true`, **strict comparison** is used: both the value and the type must match (`===`).
    //             * When omitted or `false`, **loose comparison** is used: values are compared using `==`, which can lead to unintended matches due to type juggling in PHP.

    //                 So this line:

    //                
    //                 in_array($status, $allowed, true)
    //                

    //                 means PHP will only return `true` if `$status` is *exactly* equal in both value and type to an item in `$allowed`.

    //                 _________

    //                 ### Why it's included in the second check and not in the first

    //                 The difference likely exists because the **second array contains `null`**, and possibly different data types (like strings, integers, etc.). Using strict comparison ensures that `null` is only matched against an actual `null`, not something loosely equivalent like `0` or `''` (empty string), which could happen in loose mode.

    //                 In contrast, the first check:

    //                
    //                 in_array($status, $allowed)
    //                 

    //                 may only be dealing with predefined constants (likely all strings or integers), and the developer assumed type juggling would not lead to incorrect matches.

    //                 _________

    //             ### Summary:

    //             *  > `in_array(..., ..., true)` → Use this when **types matter** (e.g., distinguishing `null`, `0`, `'0'`, etc.).
    //             *  > Without `true` → Loose comparison, can be risky if data types vary.
    //             * **Why the difference?** Probably because the second check handles more sensitive or mixed-type values, like `null`.

    //             If both filters may receive mixed-type input (e.g., from a request), it would be safer to **use strict mode in both**.
    // 
*/