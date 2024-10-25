<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Api\V1\General\SMS\SMSService;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct($phoneNo, $msg)
    {
        //
        $this->phoneNumber = $phoneNo;
        $this->message = $msg;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        // Logic for sending the SMS message
        SMSService::sendSms($this->phoneNumber, $this->message);
    }
}
