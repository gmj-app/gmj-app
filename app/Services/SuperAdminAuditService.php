<?php

namespace App\Services;

use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SuperAdminAuditService
{
    public function record(User $admin, Model $auditable, string $action, string $description, array $before = [], array $after = [], array $metadata = [], ?Request $request = null): SuperAdminAuditLog
    {
        return SuperAdminAuditLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'description' => $description,
            'before_data' => $before ?: null,
            'after_data' => $after ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
