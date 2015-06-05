<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClumsyNotifiablesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clumsy_notifiables', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('notification_id')->unsigned();
			$table->string('notifiable_type');
			$table->integer('notifiable_id')->unsigned();
			$table->boolean('triggered')->default(0);
			$table->boolean('read')->default(0);

			$table->foreign('notification_id')->references('id')->on('clumsy_notifications')->onDelete('cascade');
			$table->index('notifiable_type');
			$table->index('notifiable_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('clumsy_notifiables', function($table)
		{
			$table->dropForeign('clumsy_notifiables_notification_id_foreign');
		});

		Schema::drop('clumsy_notifiables');
	}

}
