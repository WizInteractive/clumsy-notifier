<?php namespace Clumsy\Notifier;

use Illuminate\Support\ServiceProvider;

class NotifierServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['clumsy.notifier'] = new Notifier;

        $this->app['command.clumsy.trigger-pending-notifications'] = $this->app->share(function($app)
            {
                return new Console\TriggerPendingNotificationsCommand();
            });

        $this->commands(array('command.clumsy.trigger-pending-notifications'));
	}

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$path = __DIR__.'/../..';

        $this->package('clumsy/notifier', 'clumsy/notifier', $path);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'clumsy.notifier',
		);
	}

}
