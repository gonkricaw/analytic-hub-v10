<?php

namespace App\Events;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a notification is sent to users
 * 
 * This event is broadcast to specific users via private channels
 * to enable real-time notification delivery in the frontend.
 * 
 * @package App\Events
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The notification that was sent
     *
     * @var Notification
     */
    public $notification;

    /**
     * The user who received the notification
     *
     * @var User
     */
    public $user;

    /**
     * Additional data for the notification
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param Notification $notification The notification that was sent
     * @param User $user The user who received the notification
     * @param array $data Additional notification data
     */
    public function __construct(Notification $notification, User $user, array $data = [])
    {
        $this->notification = $notification;
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id)
        ];
    }

    /**
     * Get the event name for broadcasting
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'priority' => $this->notification->priority,
            'category' => $this->notification->category,
            'action_url' => $this->notification->action_url,
            'action_text' => $this->notification->action_text,
            'created_at' => $this->notification->created_at->toISOString(),
            'expires_at' => $this->notification->expires_at?->toISOString(),
            'user_id' => $this->user->id,
            'unread_count' => $this->user->unreadNotifications()->count(),
            'data' => $this->data,
        ];
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if the notification is active and not expired
        return $this->notification->status === 'sent' && 
               ($this->notification->expires_at === null || $this->notification->expires_at->isFuture());
    }
}