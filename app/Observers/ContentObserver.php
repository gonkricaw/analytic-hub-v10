<?php

namespace App\Observers;

use App\Models\Content;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Class ContentObserver
 * 
 * Observes Content model events and logs activities for audit trail.
 * Tracks content creation, updates, publication, and deletion events.
 * 
 * @package App\Observers
 */
class ContentObserver
{
    /**
     * Handle the Content "created" event.
     * 
     * @param Content $content
     * @return void
     */
    public function created(Content $content): void
    {
        $this->logActivity('created', $content, [
            'content_id' => $content->id,
            'title' => $content->title,
            'slug' => $content->slug,
            'type' => $content->type,
            'status' => $content->status,
            'category' => $content->category,
        ]);
    }

    /**
     * Handle the Content "updated" event.
     * 
     * @param Content $content
     * @return void
     */
    public function updated(Content $content): void
    {
        $changes = $content->getChanges();
        $original = $content->getOriginal();
        
        if (!empty($changes)) {
            // Check for status changes (especially publishing)
            $statusChanged = isset($changes['status']);
            $published = $statusChanged && $changes['status'] === 'published';
            
            $this->logActivity('updated', $content, [
                'content_id' => $content->id,
                'title' => $content->title,
                'changes' => $changes,
                'original' => array_intersect_key($original, $changes),
                'status_changed' => $statusChanged,
                'published' => $published,
            ]);
            
            // Log separate activity for publishing
            if ($published) {
                $this->logActivity('published', $content, [
                    'content_id' => $content->id,
                    'title' => $content->title,
                    'published_at' => $content->published_at,
                ]);
            }
        }
    }

    /**
     * Handle the Content "deleted" event.
     * 
     * @param Content $content
     * @return void
     */
    public function deleted(Content $content): void
    {
        $this->logActivity('deleted', $content, [
            'content_id' => $content->id,
            'title' => $content->title,
            'slug' => $content->slug,
            'type' => $content->type,
            'status' => $content->status,
            'deleted_at' => $content->deleted_at,
        ]);
    }

    /**
     * Handle the Content "restored" event.
     * 
     * @param Content $content
     * @return void
     */
    public function restored(Content $content): void
    {
        $this->logActivity('restored', $content, [
            'content_id' => $content->id,
            'title' => $content->title,
            'restored_at' => now(),
        ]);
    }

    /**
     * Log activity to the activity log.
     * 
     * @param string $action
     * @param Content $content
     * @param array $properties
     * @return void
     */
    private function logActivity(string $action, Content $content, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'subject_type' => Content::class,
                'subject_id' => $content->id,
                'action' => $action,
                'description' => "Content {$action}: {$content->title}",
                'properties' => $properties,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
                'is_sensitive' => in_array($action, ['deleted', 'restored']),
                'severity' => $this->getSeverity($action),
                'category' => 'content_management',
                'tags' => ['content', $content->type, $action],
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log content activity', [
                'action' => $action,
                'content_id' => $content->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get severity level for the action.
     * 
     * @param string $action
     * @return string
     */
    private function getSeverity(string $action): string
    {
        return match ($action) {
            'deleted' => 'high',
            'published', 'restored' => 'medium',
            'created', 'updated' => 'low',
            default => 'info',
        };
    }
}