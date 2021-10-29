<?php

/**
 * @file classes/migration/EasySubscribeSchemaMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EasySubscribeSchemaMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class EasySubscribeSchemaMigration extends Migration {
        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
		// List of emails for easy subscribe
		Capsule::schema()->create('easysubscribe_emails', function (Blueprint $table) {
			$table->bigInteger('context_id');
			$table->bigInteger('easysubscribe_email_id')->autoIncrement();
			$table->string('email', 255);
			$table->string('locale', 255);
			$table->tinyInteger('active');
		});
	}
	public function check() {
		return Capsule::schema()->hasTable('easysubscribe_emails');
	}
}