<?php namespace Clumsy\Notifier\Models;

use Clumsy\Notifier\Facade as Notifier;

class Notification extends \Eloquent {

    protected $guarded = array('id');

    public $timestamps = false;

    public function getDates()
    {
        return array(
            'visible_from',
        );
    }

    public function meta()
    {
        return $this->hasMany('\Clumsy\Notifier\Models\NotificationMeta');
    }

    public function metaArray()
    {
        return $this->meta->lists('value', 'key');
    }

    public function metaValue($key)
    {
        return array_get($this->metaArray(), $key);
    }

    protected function resolve()
    {
        $attributes = $this->toArray();

        $attributes = array_dot(array_except($attributes, array('pivot', 'meta')) + array_get($attributes, 'pivot'));

        // Attributes passed to translation function is an array of flattened
        // notification information plus "resolved" notification meta
        $attributes = $attributes + Notifier::resolve($this->metaArray(), $this);

        return trans("notifications.{$this->slug}", $attributes);
    }

    public function __toString()
    {
        return $this->resolve();
    }
}