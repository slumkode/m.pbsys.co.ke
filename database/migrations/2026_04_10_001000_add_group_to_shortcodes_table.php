<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupToShortcodesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('shortcodes') || Schema::hasColumn('shortcodes', 'group')) {
            return;
        }

        Schema::table('shortcodes', function (Blueprint $table) {
            $table->string('group')->nullable()->after('shortcode_type');
            $table->index('group', 'shortcodes_group_lookup_index');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('shortcodes') || ! Schema::hasColumn('shortcodes', 'group')) {
            return;
        }

        Schema::table('shortcodes', function (Blueprint $table) {
            $table->dropIndex('shortcodes_group_lookup_index');
            $table->dropColumn('group');
        });
    }
}
