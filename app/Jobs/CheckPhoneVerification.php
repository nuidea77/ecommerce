<?php

namespace App\Jobs;

use App\Models\PhoneVerification;
use App\Services\VerifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs after the callback response is sent, so verify.mn gets its 2xx fast.
 */
class CheckPhoneVerification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $verificationId) {}

    public function handle(VerifyService $verify): void
    {
        $verification = PhoneVerification::find($this->verificationId);
        if ($verification) {
            $verify->checkSession($verification);
        }
    }
}
