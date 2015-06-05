<?php namespace Clumsy\Notifier\Console;

use Illuminate\Foundation\AssetPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Clumsy\Notifier\Models\Notification;

/**
 * Check for un-triggered notifications and trigger them
 *
 * @author Tomas Buteler <tbuteler@gmail.com>
 */
class TriggerPendingNotificationsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clumsy:trigger-pending-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for un-triggered notifications and trigger them';

    /**
     * The asset publisher instance.
     *
     * @var \Illuminate\Foundation\AssetPublisher
     */
    protected $assets;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $pending = DB::table('clumsy_notifiables')
                     ->join('clumsy_notifications', 'clumsy_notifications.id', '=', 'clumsy_notifiables.notification_id')
                     ->where('triggered', false)
                     ->where('visible_from', '<=', Carbon::now()->toDateTimeString())
                     ->count();

        if (!$pending)
        {
            $this->info("No pending notifications to trigger");
        }

        Notification::with('meta')
                    ->select('*', 'clumsy_notifiables.id as pivot_id')
                    ->join('clumsy_notifiables', 'clumsy_notifications.id', '=', 'clumsy_notifiables.notification_id')
                    ->where('triggered', false)
                    ->where('visible_from', '<=', Carbon::now()->toDateTimeString())
                    ->chunk(200, function($notifications)
                    {
                        foreach ($notifications as $notification)
                        {
                            $model = $notification->notifiable_type;
                            $target = $model::find($notification->notifiable_id);
                            if ($target)
                            {
                                $target->triggerNotification($notification);
                            }
                        }

                        DB::table('clumsy_notifiables')
                          ->whereIn('id', $notifications->lists('pivot_id'))
                          ->update(array('triggered' => true));

                        $count = count($notifications);
                        $this->info("Triggered {$count} notifications");
                    });
        
        $this->info("All pending notifications triggered");
    }

}
