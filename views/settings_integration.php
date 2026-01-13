<?php defined('BASEPATH') or exit('No direct script access allowed'); 
$CI = &get_instance();
$CI->load->model('cyborg_push/cyborg_push_model');
$subscriptions = $CI->cyborg_push_model->get_subscriptions();
$logs = $CI->cyborg_push_model->get_logs(100);
?>

<!-- Cyborg Push Notifications Settings -->
<h4 class="no-margin font-bold"><?php echo _l('cyborg_push'); ?></h4>
<p class="text-muted"><?php echo _l('cyborg_push_description'); ?></p>
<hr class="hr-panel-heading" />

<!-- Tabs -->
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active">
        <a href="#cyborg_push_settings_tab" aria-controls="cyborg_push_settings_tab" role="tab" data-toggle="tab">
            <i class="fa fa-cog"></i> <?php echo _l('cyborg_push_settings'); ?>
        </a>
    </li>
    <li role="presentation">
        <a href="#cyborg_push_subscriptions_tab" aria-controls="cyborg_push_subscriptions_tab" role="tab" data-toggle="tab">
            <i class="fa fa-users"></i> <?php echo _l('cyborg_push_subscriptions'); ?> 
            <span class="badge"><?php echo count($subscriptions); ?></span>
        </a>
    </li>
    <li role="presentation">
        <a href="#cyborg_push_logs_tab" aria-controls="cyborg_push_logs_tab" role="tab" data-toggle="tab">
            <i class="fa fa-list"></i> <?php echo _l('cyborg_push_logs'); ?>
        </a>
    </li>
</ul>

<div class="tab-content mtop15">
    <!-- Settings Tab -->
    <div role="tabpanel" class="tab-pane active" id="cyborg_push_settings_tab">
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
            </div>
        </div>
    </div>

    <!-- Subscriptions Tab -->
    <div role="tabpanel" class="tab-pane" id="cyborg_push_subscriptions_tab">
        <?php if (empty($subscriptions)): ?>
            <p class="text-muted"><?php echo _l('cyborg_push_no_subscriptions'); ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo _l('cyborg_push_subscription_user'); ?></th>
                            <th><?php echo _l('cyborg_push_subscription_device'); ?></th>
                            <th><?php echo _l('cyborg_push_subscription_active'); ?></th>
                            <th><?php echo _l('cyborg_push_subscription_created'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><?php echo $sub['id']; ?></td>
                            <td><?php echo $sub['user_name']; ?></td>
                            <td><?php echo ucfirst($sub['device_type']); ?></td>
                            <td>
                                <?php if ($sub['is_active']): ?>
                                    <span class="label label-success"><?php echo _l('yes'); ?></span>
                                <?php else: ?>
                                    <span class="label label-danger"><?php echo _l('no'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo _dt($sub['created_at']); ?></td>
                            <td>
                                <a href="<?php echo admin_url('cyborg_push/delete_subscription/' . $sub['id']); ?>" class="btn btn-danger btn-xs _delete">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Logs Tab -->
    <div role="tabpanel" class="tab-pane" id="cyborg_push_logs_tab">
        <div class="mbot15">
            <a href="<?php echo admin_url('cyborg_push/clear_logs'); ?>" class="btn btn-warning btn-sm">
                <i class="fa fa-clock-o"></i> <?php echo _l('cyborg_push_clear_logs'); ?>
            </a>
            <a href="<?php echo admin_url('cyborg_push/clear_all_logs'); ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo _l('cyborg_push_clear_all_logs_confirm'); ?>');">
                <i class="fa fa-trash"></i> <?php echo _l('cyborg_push_clear_all_logs'); ?>
            </a>
        </div>
        
        <?php if (empty($logs)): ?>
            <p class="text-muted"><?php echo _l('cyborg_push_no_logs'); ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo _l('cyborg_push_subscription_user'); ?></th>
                            <th><?php echo _l('cyborg_push_log_title'); ?></th>
                            <th><?php echo _l('cyborg_push_log_status'); ?></th>
                            <th><?php echo _l('cyborg_push_log_date'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $status_class = [
                            'sent' => 'success',
                            'failed' => 'danger',
                            'pending' => 'warning',
                            'expired' => 'default'
                        ];
                        foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo $log['user_name']; ?></td>
                            <td><?php echo htmlspecialchars($log['title']); ?></td>
                            <td>
                                <span class="label label-<?php echo $status_class[$log['status']] ?? 'default'; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </td>
                            <td><?php echo _dt($log['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
