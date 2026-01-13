<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="no-margin font-bold"><?php echo $title; ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('cyborg_push/clear_logs'); ?>" class="btn btn-warning">
                                    <i class="fa fa-clock-o"></i> <?php echo _l('cyborg_push_clear_logs'); ?>
                                </a>
                                <a href="<?php echo admin_url('cyborg_push/clear_all_logs'); ?>" class="btn btn-danger _delete" onclick="return confirm('<?php echo _l('cyborg_push_clear_all_logs_confirm'); ?>');">
                                    <i class="fa fa-trash"></i> <?php echo _l('cyborg_push_clear_all_logs'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        
                        <?php if (empty($logs)): ?>
                            <p class="text-muted"><?php echo _l('cyborg_push_no_logs'); ?></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped dt-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th><?php echo _l('cyborg_push_subscription_user'); ?></th>
                                            <th><?php echo _l('cyborg_push_log_title'); ?></th>
                                            <th><?php echo _l('cyborg_push_log_body'); ?></th>
                                            <th><?php echo _l('cyborg_push_log_status'); ?></th>
                                            <th><?php echo _l('cyborg_push_log_date'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td><?php echo $log['user_name']; ?></td>
                                            <td><?php echo htmlspecialchars($log['title']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($log['body'], 0, 100)); ?></td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'sent' => 'success',
                                                    'failed' => 'danger',
                                                    'pending' => 'warning',
                                                    'expired' => 'default'
                                                ];
                                                ?>
                                                <span class="label label-<?php echo $status_class[$log['status']] ?? 'default'; ?>">
                                                    <?php echo ucfirst($log['status']); ?>
                                                </span>
                                                <?php if (!empty($log['error_message'])): ?>
                                                    <i class="fa fa-info-circle text-danger" title="<?php echo htmlspecialchars($log['error_message']); ?>"></i>
                                                <?php endif; ?>
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
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
