<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin font-bold"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <?php echo form_open(admin_url('cyborg_push/settings')); ?>
                        
                        <!-- General Settings -->
                        <div class="form-group">
                            <label class="control-label">
                                <input type="checkbox" name="cyborg_push_enabled" value="1" <?php echo get_option('cyborg_push_enabled') == '1' ? 'checked' : ''; ?>>
                                <?php echo _l('cyborg_push_enabled'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label">
                                <input type="checkbox" name="cyborg_push_clients_enabled" value="1" <?php echo get_option('cyborg_push_clients_enabled') == '1' ? 'checked' : ''; ?>>
                                <?php echo _l('cyborg_push_clients_enabled'); ?>
                            </label>
                        </div>
                        
                        <hr />
                        
                        <!-- VAPID Keys -->
                        <h5 class="font-bold"><?php echo _l('cyborg_push_vapid_keys'); ?></h5>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_vapid_public_key'); ?></label>
                            <input type="text" name="cyborg_push_vapid_public_key" class="form-control" value="<?php echo get_option('cyborg_push_vapid_public_key'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_vapid_private_key'); ?></label>
                            <input type="password" name="cyborg_push_vapid_private_key" class="form-control" value="<?php echo get_option('cyborg_push_vapid_private_key'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_vapid_subject'); ?></label>
                            <input type="text" name="cyborg_push_vapid_subject" class="form-control" placeholder="mailto:admin@example.com" value="<?php echo get_option('cyborg_push_vapid_subject'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="generate_vapid_keys" value="1" class="btn btn-default">
                                <i class="fa fa-key"></i> <?php echo _l('cyborg_push_generate_vapid'); ?>
                            </button>
                        </div>
                        
                        <hr />
                        
                        <!-- Default Icons -->
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_default_icon'); ?></label>
                            <input type="text" name="cyborg_push_default_icon" class="form-control" value="<?php echo get_option('cyborg_push_default_icon'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_default_badge'); ?></label>
                            <input type="text" name="cyborg_push_default_badge" class="form-control" value="<?php echo get_option('cyborg_push_default_badge'); ?>">
                        </div>
                        
                        <hr />
                        
                        <!-- FCM Settings -->
                        <h5 class="font-bold"><?php echo _l('cyborg_push_fcm_settings'); ?></h5>
                        
                        <div class="form-group">
                            <label class="control-label">
                                <input type="checkbox" name="cyborg_push_fcm_enabled" value="1" <?php echo get_option('cyborg_push_fcm_enabled') == '1' ? 'checked' : ''; ?>>
                                <?php echo _l('cyborg_push_fcm_enabled'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_fcm_server_key'); ?></label>
                            <input type="password" name="cyborg_push_fcm_server_key" class="form-control" value="<?php echo get_option('cyborg_push_fcm_server_key'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_fcm_sender_id'); ?></label>
                            <input type="text" name="cyborg_push_fcm_sender_id" class="form-control" value="<?php echo get_option('cyborg_push_fcm_sender_id'); ?>">
                        </div>
                        
                        <hr />
                        
                        <!-- OneSignal Settings -->
                        <h5 class="font-bold"><?php echo _l('cyborg_push_onesignal_settings'); ?></h5>
                        
                        <div class="form-group">
                            <label class="control-label">
                                <input type="checkbox" name="cyborg_push_onesignal_enabled" value="1" <?php echo get_option('cyborg_push_onesignal_enabled') == '1' ? 'checked' : ''; ?>>
                                <?php echo _l('cyborg_push_onesignal_enabled'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_onesignal_app_id'); ?></label>
                            <input type="text" name="cyborg_push_onesignal_app_id" class="form-control" value="<?php echo get_option('cyborg_push_onesignal_app_id'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_onesignal_api_key'); ?></label>
                            <input type="password" name="cyborg_push_onesignal_api_key" class="form-control" value="<?php echo get_option('cyborg_push_onesignal_api_key'); ?>">
                        </div>
                        
                        <hr />
                        
                        <!-- Other Settings -->
                        <div class="form-group">
                            <label class="control-label"><?php echo _l('cyborg_push_log_retention_days'); ?></label>
                            <input type="number" name="cyborg_push_log_retention_days" class="form-control" value="<?php echo get_option('cyborg_push_log_retention_days'); ?>" min="1" max="365">
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label">
                                <input type="checkbox" name="cyborg_push_disable_pusher" value="1" <?php echo get_option('cyborg_push_disable_pusher') == '1' ? 'checked' : ''; ?>>
                                <?php echo _l('cyborg_push_disable_pusher'); ?>
                            </label>
                        </div>
                        
                        <hr />
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                            <a href="<?php echo admin_url('cyborg_push/test_notification'); ?>" class="btn btn-info">
                                <i class="fa fa-bell"></i> <?php echo _l('cyborg_push_test_notification'); ?>
                            </a>
                        </div>
                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
