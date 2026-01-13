<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Cyborg Push Notifications Settings -->
<h4 class="no-margin font-bold"><?php echo _l('cyborg_push'); ?></h4>
<p class="text-muted"><?php echo _l('cyborg_push_description'); ?></p>
<hr class="hr-panel-heading" />

<div class="row">
    <div class="col-md-6">
        <?php echo render_yes_no_option('cyborg_push_enabled', 'cyborg_push_enabled'); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_yes_no_option('cyborg_push_disable_pusher', 'cyborg_push_disable_pusher'); ?>
        <p class="text-muted mtop5"><?php echo _l('cyborg_push_disable_pusher_help'); ?></p>
    </div>
</div>

<hr />

<!-- VAPID Keys -->
<h5 class="font-bold"><?php echo _l('cyborg_push_vapid_keys'); ?></h5>

<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[cyborg_push_vapid_public_key]', 'cyborg_push_vapid_public_key', get_option('cyborg_push_vapid_public_key')); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[cyborg_push_vapid_private_key]', 'cyborg_push_vapid_private_key', get_option('cyborg_push_vapid_private_key'), 'password'); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[cyborg_push_vapid_subject]', 'cyborg_push_vapid_subject', get_option('cyborg_push_vapid_subject'), 'text', ['placeholder' => 'mailto:admin@example.com']); ?>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label">&nbsp;</label>
            <div>
                <a href="<?php echo admin_url('cyborg_push/generate_vapid'); ?>" class="btn btn-default">
                    <i class="fa fa-key"></i> <?php echo _l('cyborg_push_generate_vapid'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<hr />

<!-- Additional Settings -->
<div class="row">
    <div class="col-md-6">
        <?php echo render_input('settings[cyborg_push_default_icon]', 'cyborg_push_default_icon', get_option('cyborg_push_default_icon')); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[cyborg_push_default_badge]', 'cyborg_push_default_badge', get_option('cyborg_push_default_badge')); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <?php echo render_input('settings[cyborg_push_log_retention_days]', 'cyborg_push_log_retention_days', get_option('cyborg_push_log_retention_days') ?: 30, 'number', ['min' => 1, 'max' => 365]); ?>
    </div>
</div>

<div class="row mtop15">
    <div class="col-md-12">
        <a href="<?php echo admin_url('cyborg_push/test_notification'); ?>" class="btn btn-info">
            <i class="fa fa-bell"></i> <?php echo _l('cyborg_push_test_notification'); ?>
        </a>
        <a href="<?php echo admin_url('cyborg_push/subscriptions'); ?>" class="btn btn-default">
            <i class="fa fa-users"></i> <?php echo _l('cyborg_push_subscriptions'); ?>
        </a>
        <a href="<?php echo admin_url('cyborg_push/logs'); ?>" class="btn btn-default">
            <i class="fa fa-list"></i> <?php echo _l('cyborg_push_logs'); ?>
        </a>
    </div>
</div>
