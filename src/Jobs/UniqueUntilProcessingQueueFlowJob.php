<?php

declare(strict_types=1);

namespace B7s\QueueFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class UniqueUntilProcessingQueueFlowJob extends QueueFlowJob implements ShouldBeUniqueUntilProcessing
{
}
