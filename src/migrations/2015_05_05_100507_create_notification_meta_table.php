<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNotificationMetaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('notification_meta', function(Blueprint $table)
		{
			$table->integer('notification_id')->unsigned();
			$table->string('key');
			$table->text('value')->nullable()->default(null);

			$table->foreign('notification_id')->references('id')->on('notifications');
			$table->index('key');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('notification_meta', function($table)
		{
			$table->dropForeign('notification_meta_notification_id_foreign');
		});

		Schema::drop('notification_meta');
	}

}
