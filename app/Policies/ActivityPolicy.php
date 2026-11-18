<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('activity_logs.view_any');
    }

    public function view(User $actor, Activity $activity): bool
    {
        return $actor->can('activity_logs.view');
    }
}
