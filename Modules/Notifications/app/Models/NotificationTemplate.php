<?php

namespace Modules\Notifications\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $table = 'notification_templates';

    protected $fillable = [
        'name',
        'title',
        'subject',
        'email_template',
        'sms_template',
        'push_template',
        'in_app_template',
        'channels',
        'priority',
        'category',
        'description',
        'is_active'
    ];

    protected $casts = [
        'channels' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get notifications using this template
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by template name
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
