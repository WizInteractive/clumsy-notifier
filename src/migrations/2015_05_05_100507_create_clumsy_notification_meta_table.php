<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClumsyNotificationMetaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clumsy_notification_meta', function(Blueprint $table)
		{
			$table->integer('notification_id')->unsigned();
			$table->string('key');
			$table->text('value')->nullable()->default(null);

			$table->foreign('notification_id')->references('id')->on('clumsy_notifications')->onDelete('cascade');
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
		Schema::table('clumsy_notification_meta', function($table)
		{
			$table->dropForeign('clumsy_notification_meta_notification_id_foreign');
		});

		Schema::drop('clumsy_notification_meta');
	}

}
