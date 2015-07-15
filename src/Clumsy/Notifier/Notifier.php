<?php namespace Clumsy\Notifier;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Clumsy\Notifier\Models\Notification;
use Clumsy\Notifier\Models\NotificationMeta;
use Carbon\Carbon;

class Notifier {

	protected $general_title_resolver;
	protected $default_title;

	protected $resolvers = array(
		'title'   => array(),
		'meta'    => array(),
		'content' => array(),
	);

	public function __construct()
	{
		$this->general_title_resolver = function()
		{
			return $this->defaultTitle();
		};
	}

	public function create($notification_array = array(), $visible_from = false)
	{
		$notification_array = (array)$notification_array;

		if (!$visible_from)
		{
			$visible_from = Carbon::now();
		}

		$attributes = array();
		array_walk($notification_array, function($value, $key) use(&$attributes)
		{
			if (!$key)
			{
				$attributes = array(
					$value => array()
				);
			}
			else
			{
				$attributes[$key] = $value;
			}
		});

		$notification = Notification::create(array(
			'slug'         => key($attributes),
			'visible_from' => $visible_from->toDateTimeString(),
		));

		$metaModels = array();
		foreach (head($attributes) as $key => $value)
		{
			$metaModels[] = new NotificationMeta(compact('key', 'value'));
		}

		$notification->meta()->saveMany($metaModels);

		return $notification;
	}

	public function batchOrSingle(Notification $notification, $target)
	{
		if (!($target instanceof Collection))
		{
			$target = new Collection(array($target));
		}

		return $this->batch($notification, $target);
	}

	public function batch(Notification $notification, Collection $items)
	{
		foreach($items as $item)
		{
			$item->dispatchNotification($notification);
		}
	}
	
	public function notify($attributes = array(), $target = null, $visible_from = false)
	{
		if (!$target)
		{
			return false;
		}

		return $this->batchOrSingle($this->create($attributes, $visible_from), $target);
	}

	public function setGeneralTitleResolver(Closure $callback)
	{
		$this->general_title_resolver = $callback;
	}

	public function setDefaultTitle($title)
	{
		$this->default_title = $title;
	}

	public function defaultTitle()
	{
		return $this->default_title ? $this->default_title : trans('clumsy/notifier::notifications.default-title');
	}

	public function setResolver($type, $slug, Closure $callback)
	{
		$this->resolvers[$type][$slug] = $callback;
	}

	public function getResolver($type, $slug)
	{
		return array_get($this->resolvers, "{$type}.{$slug}");
	}

	public function hasResolver($type, $slug)
	{
		$resolver = $this->getResolver($type, $slug);

		return ($resolver instanceof Closure);
	}

	public function titleResolver($slugs, Closure $callback)
	{
		foreach ((array)$slugs as $slug)
		{
			$this->setResolver('title', $slug, $callback);
		}
	}

	public function resolveTitle(Notification $notification)
	{
		$callback = $this->hasResolver('title', $notification->slug)
					? $this->getResolver('title', $notification->slug)
					: $this->general_title_resolver;

		return $callback($notification);
	}

	public function metaResolver($slugs, Closure $callback)
	{
		foreach ((array)$slugs as $slug)
		{
			$this->setResolver('meta', $slug, $callback);
		}
	}

	public function resolveMeta($meta, $notification)
	{
		if ($this->hasResolver('meta', $notification->slug))
		{
			$callback = $this->getResolver('meta', $notification->slug);

			return $callback($meta, $notification);
		}

		return $meta;
	}

	public function resolver($slugs, Closure $callback)
	{
		foreach ((array)$slugs as $slug)
		{
			$this->setResolver('content', $slug, $callback);
		}
	}

	public function resolve(&$notification)
	{
		if ($this->hasResolver('content', $notification->slug))
		{
			$callback = $this->getResolver('content', $notification->slug);

			$notification->content = $callback($notification);
		}
		else
		{
			$notification->content = trans("clumsy/notifier::notifications.{$notification->slug}", $notification->meta_attributes);
		}
	}

	public function mail(Notification $notification, array $recipients)
	{
		$subject = $notification->title ? $notification->title : $this->resolveTitle($notification);

		$view = View::exists("clumsy/notifier::emails.{$notification->slug}")
				? "clumsy/notifier::emails.{$notification->slug}"
				: "clumsy/notifier::email";

		Mail::send($view, compact('notification'), function($message) use($recipients, $subject)
		{
		    foreach ($recipients as $address => $recipient)
		    {
		    	if (!$address)
		    	{
					// Allow recipients to be non-associative array of addresses
		    		$address = $recipient;
		    	}

		    	$message->to($address, $recipient)->subject($subject);
		    }
		});
	}

	public function deleteByMeta($meta_key, $meta_value = null)
	{
		Notification::whereIn('id', function($query) use($meta_key, $meta_value)
		{
			$query->select('notification_id')
				  ->from('notification_meta')
				  ->where('key', $meta_key);

			if ($meta_value)
			{
				$query->where('value', $meta_value);
			}
		})
		->delete();
	}

	public function dissociate($association_type, $association_id, $options = array())
	{
		$defaults = array(
			'triggered'  => false,
			'slug'       => false,
			'meta_key'   => false,
			'meta_value' => false,
		);

		$options = array_merge($defaults, $options);

        $query = DB::table('notification_associations')
				   ->select('notification_associations.id')
				   ->join('notifications', 'notifications.id', '=', 'notification_associations.notification_id')
				   ->join('notification_meta', 'notifications.id', '=', 'notification_meta.notification_id')
				   ->where('notification_association_type', $association_type)
				   ->where('notification_association_id', $association_id)
				   ->where('triggered', $options['triggered']);

		if ($options['slug'])
		{
			$query->where('slug', $options['slug']);
		}

		if ($options['meta_key'])
		{
			$query->where('key', $options['meta_key']);
		}

		if ($options['meta_value'])
		{
			$query->where('value', $options['meta_value']);
		}
							
		$notifications = $query->lists('id');

        if (sizeof($notifications))
        {
        	return DB::table('notification_associations')->whereIn('id', $notifications)->delete();
        }

        return false;
	}
}