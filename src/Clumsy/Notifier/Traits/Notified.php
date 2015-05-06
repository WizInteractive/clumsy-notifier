<?php namespace Clumsy\Notifier\Traits;

use Illuminate\Support\Facades\DB;
use Clumsy\Notifier\Facade as Notifier;
use Clumsy\Notifier\Models\Notification;
use Carbon\Carbon;

trait Notified {

    public function notifier()
    {
        return $this->morphToMany('\Clumsy\Notifier\Models\Notification', 'notification_association');
    }

    public function baseNotifier()
    {
        return $this->morphToMany('\Clumsy\Notifier\Models\Notification', 'notification_association')
                    ->where('visible_from', '<=', Carbon::now()->toDateTimeString())
                    ->orderBy('visible_from', 'desc');
    }

    public function allNotifications()
    {
        return $this->baseNotifier()
                    ->withPivot('read')
                    ->with('meta');
    }

    public function readNotifications()
    {
        return $this->allNotifications()->where('notification_associations.read', 1);
    }

    public function unreadNotifications()
    {
        return $this->allNotifications()->where('notification_associations.read', 0);
    }

    public function updateReadStatus($read = true, $notification_id = false)
    {
        $query = DB::table('notification_associations');

        if ($notification_id)
        {
            $query->where('notification_id', $notification_id);
        }
        
        return $query->where('notification_association_type', class_basename(get_class($this)))
                     ->where('notification_association_id', $this->id)
                     ->update(array('read' => (int)$read));
    }

    public function markAllNotificationsAsRead()
    {
        return $this->updateReadStatus(true);
    }

    public function markNotificationAsRead($notification_id)
    {
        return $this->updateReadStatus(true, $notification_id);
    }

    public function markNotificationAsUnread($notification_id)
    {
        return $this->updateReadStatus(false, $notification_id);
    }

    public function dispatchNotification(Notification $notification)
    {
        $this->notifier()->attach($notification->id);
    }

    public function notify($attributes = array(), $visible_from = false)
    {
        Notifier::notify($attributes, $this, $visible_from);

        return $this;
    }
}