<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        // Bug fix for SQL escaping - no database changes needed
        // This migration just updates the version
    }
}
