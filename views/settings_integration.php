<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Cyborg Push Notifications -->
<hr class="hr-panel-heading" />
<h4 class="no-margin font-bold"><?php echo _l('cyborg_push'); ?></h4>
<p class="text-muted"><?php echo _l('cyborg_push_description'); ?></p>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label">
                <input type="checkbox" name="cyborg_push_enabled" value="1" <?php echo $enabled == '1' ? 'checked' : ''; ?>>
                <?php echo _l('cyborg_push_enabled'); ?>
            </label>
        </div>
        
        <div class="form-group">
            <label class="control-label">
                <input type="checkbox" name="cyborg_push_disable_pusher" value="1" <?php echo $disable_pusher == '1' ? 'checked' : ''; ?>>
                <?php echo _l('cyborg_push_disable_pusher'); ?>
            </label>
            <p class="text-muted"><?php echo _l('cyborg_push_disable_pusher_help'); ?></p>
        </div>
    </div>
</div>

<hr />

<!-- VAPID Keys -->
<h5 class="font-bold"><?php echo _l('cyborg_push_vapid_keys'); ?></h5>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label"><?php echo _l('cyborg_push_vapid_public_key'); ?></label>
            <input type="text" name="cyborg_push_vapid_public_key" class="form-control" value="<?php echo $vapid_public_key; ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label"><?php echo _l('cyborg_push_vapid_private_key'); ?></label>
            <input type="password" name="cyborg_push_vapid_private_key" class="form-control" value="<?php echo $vapid_private_key; ?>">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label"><?php echo _l('cyborg_push_vapid_subject'); ?></label>
            <input type="text" name="cyborg_push_vapid_subject" class="form-control" placeholder="mailto:admin@example.com" value="<?php echo $vapid_subject; ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label">&nbsp;</label>
            <button type="submit" name="generate_vapid_keys" value="1" class="btn btn-default btn-block">
                <i class="fa fa-key"></i> <?php echo _l('cyborg_push_generate_vapid'); ?>
            </button>
        </div>
    </div>
</div>

<hr />

<!-- Additional Settings -->
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label"><?php echo _l('cyborg_push_default_icon'); ?></label>
            <input type="text" name="cyborg_push_default_icon" class="form-control" value="<?php echo $default_icon; ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label"><?php echo _l('cyborg_push_default_badge'); ?></label>
            <input type="text" name="cyborg_push_default_badge" class="form-control" value="<?php echo $default_badge; ?>">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label class="control-label"><?php echo _l('cyborg_push_log_retention_days'); ?></label>
            <input type="number" name="cyborg_push_log_retention_days" class="form-control" value="<?php echo $log_retention_days ?: 30; ?>" min="1" max="365">
        </div>
    </div>
</div>

<div class="row">
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
