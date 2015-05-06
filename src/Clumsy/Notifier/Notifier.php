<?php namespace Clumsy\Notifier;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Clumsy\Notifier\Models\Notification;
use Clumsy\Notifier\Models\NotificationMeta;

class Notifier {

	protected $resolvers = array();

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
			$target = with(new Collection)->add($target);
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

	public function resolver($slug, Closure $callback)
	{
		$this->resolvers[$slug] = $callback;
	}

	public function resolve($meta, $notification)
	{
		if (isset($this->resolvers[$notification->slug]))
		{
			$callback = $this->resolvers[$notification->slug];

			return $callback($meta, $notification);
		}

		return $meta;
	}
}