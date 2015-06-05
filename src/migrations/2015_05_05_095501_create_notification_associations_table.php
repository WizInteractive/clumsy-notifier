<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNotificationAssociationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('notification_associations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('notification_id')->unsigned();
			$table->string('notification_association_type');
			$table->integer('notification_association_id')->unsigned();
			$table->boolean('triggered')->default(0);
			$table->boolean('read')->default(0);

			$table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
			$table->index('notification_association_type');
			$table->index('notification_association_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('notification_associations', function($table)
		{
			$table->dropForeign('notification_associations_notification_id_foreign');
		});

		Schema::drop('notification_associations');
	}

}
