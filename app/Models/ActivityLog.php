<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'action', 'description', 'ip_address'])]
class ActivityLog extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $description)
    {
        self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
