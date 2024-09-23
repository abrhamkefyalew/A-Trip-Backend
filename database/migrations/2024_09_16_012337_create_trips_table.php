<?php

use App\Models\Trip;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id()->from(100000);

            $table->foreignId('order_id')->constrained('orders'); // not nul
            $table->foreignId('driver_id')->constrained('drivers'); // not null

            $table->foreignId('organization_user_id')->nullable()->constrained('organization_users'); // the organization user who APPROVES this Trip // initially NULL // filled when the order is APPROVED by organizationUser (we fill the approvers organization_user_id)

            $table->bigInteger('start_dashboard'); // should NOT be null // filled when the Trip is started
            $table->bigInteger('end_dashboard')->nullable(); // this is NULL when trip is made initially // filled by only the driver before the trip is approved

            $table->string('source'); // should NOT be null  // filled when the Trip is started  // the starting location of the trip
            $table->string('destination')->nullable();

            $table->date('trip_date')->nullable(); // the date the trip is performed // filled by the driver // nullable and can be filled later or anytime

            $table->longText('trip_description')->nullable(); // OPTIONAL and NULLABLE // can be left Null

            $table->string('status_payment')->default(Trip::TRIP_STATUS_NOT_PAID); // this column is enum

            $table->timestamps();
            $table->softDeletes();


            // WHEN APPROVING is done by organization_user , i.e filling the column "approved_by_organization_user_id", 
            // - all  the columns must be filled : except description


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
