<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $t) {
            $t->string('image')->nullable();                    // relative URL on the public disk
            $t->string('layout', 20)->default('plain');         // plain | image_right | image_left | banner
            $t->boolean('show_in_menu')->default(false);        // show the page in the main menu
        });
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn(['image', 'layout', 'show_in_menu']));
    }
};
