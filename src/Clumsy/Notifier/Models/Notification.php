<?php namespace Clumsy\Notifier\Models;

use Carbon\Carbon;
use Clumsy\Notifier\Facade as Notifier;

class Notification extends \Eloquent {

    protected $guarded = array('id');

    protected $table = 'clumsy_notifications';

    public $timestamps = false;

    public $resolved = false;

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

    public function shouldTrigger()
    {
        return Carbon::now()->diffInSeconds($this->visible_from, false) <= 0;
    }

    protected function resolveMeta()
    {
        $attributes = $this->toArray();

        $attributes = array_dot(array_except($attributes, array('pivot', 'meta')) + array_get($attributes, 'pivot', array()));

        // Attributes passed to translation function is an array of flattened
        // notification information plus "resolved" notification meta
        return $attributes + Notifier::resolveMeta($this->metaArray(), $this);
    }

    public function resolve()
    {
        if (!$this->resolved)
        {
            $this->meta_attributes = $this->resolveMeta();
            Notifier::resolve($this, $this->meta_attributes);
            $this->resolved = true;
        }

        return $this;
    }

    public function getTitleAttribute()
    {
        if (!array_key_exists('title', $this->getAttributes()))
        {
            $this->resolve();
        }

        return array_get($this->getAttributes(), 'title');
    }

    public function getContentAttribute()
    {
        if (!array_key_exists('content', $this->getAttributes()))
        {
            $this->resolve();
        }

        return array_get($this->getAttributes(), 'content');
    }

    public function __toString()
    {
        return $this->content;
    }
}