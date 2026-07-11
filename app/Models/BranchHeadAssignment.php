<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BranchHeadAssignment extends Model
{
    protected $fillable = [
        'branch_id', 'user_id', 'effective_from', 'effective_to',
        'status', 'remarks', 'assigned_by', 'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'assigned_at' => 'datetime',
        ];
    }

    public function scopeActive($query) { return $query->where('status', 'active'); }

    public function branch()    { return $this->belongsTo(Branch::class); }
    public function user()      { return $this->belongsTo(User::class); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }

    /**
     * The one, shared "make this user the active Branch Head of this branch"
     * operation — used by both the dedicated Branch Head Assignment screen
     * and User Management (when a user is created/edited with User Type =
     * Branch Head), so both paths always produce the same auditable record
     * and the same single-active-head-per-branch guarantee.
     *
     * $attributes must include branch_id, user_id, effective_from, and may
     * include effective_to/remarks.
     */
    public static function assign(array $attributes, int $assignedById): self
    {
        return DB::transaction(function () use ($attributes, $assignedById) {
            // Whoever currently holds the branch (other than the incoming
            // user, e.g. a no-op reassignment of the same person) is being
            // displaced — remember them so their own branch-head scoping can
            // be cleared below, not just their assignment row.
            $displacedUserIds = static::where('branch_id', $attributes['branch_id'])
                ->active()
                ->where('user_id', '!=', $attributes['user_id'])
                ->pluck('user_id');

            static::where('branch_id', $attributes['branch_id'])
                ->active()
                ->update(['status' => 'inactive', 'effective_to' => now()->toDateString()]);

            $assignment = static::create(array_merge($attributes, [
                'status' => 'active',
                'assigned_by' => $assignedById,
                'assigned_at' => now(),
            ]));

            User::whereKey($attributes['user_id'])->update([
                'user_type' => 'branch_head',
                'branch_id' => $attributes['branch_id'],
                'updated_by' => $assignedById,
            ]);

            foreach ($displacedUserIds as $displacedUserId) {
                static::releaseUserIfNoActiveAssignment($displacedUserId, $assignedById);
            }

            return $assignment;
        });
    }

    /**
     * The reverse — closes this user's active assignment(s) and clears the
     * branch_head scoping off their account. Shared for the same reason.
     */
    public static function release(int $userId, ?int $actorId = null): void
    {
        static::where('user_id', $userId)
            ->active()
            ->update(['status' => 'inactive', 'effective_to' => now()->toDateString()]);

        static::releaseUserIfNoActiveAssignment($userId, $actorId);
    }

    /**
     * Clears user_type/branch_id for $userId unconditionally (the caller has
     * already ensured their assignment is closed) — deliberately does NOT
     * gate on the user's *current* user_type, since callers may invoke this
     * after the user row itself was already changed elsewhere in the same
     * request (e.g. User Management saving a new user_type in one update()).
     */
    private static function releaseUserIfNoActiveAssignment(int $userId, ?int $actorId): void
    {
        $stillActive = static::where('user_id', $userId)->active()->exists();

        if (! $stillActive) {
            User::whereKey($userId)->update([
                'user_type' => null, 'branch_id' => null, 'updated_by' => $actorId,
            ]);
        }
    }
}
