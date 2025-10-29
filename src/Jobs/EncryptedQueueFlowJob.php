<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class EncryptedQueueFlowJob extends QueueFlowJob implements ShouldBeEncrypted
{
    //
}
