<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin font-bold"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <?php if (empty($subscriptions)): ?>
                            <p class="text-muted"><?php echo _l('cyborg_push_no_subscriptions'); ?></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped dt-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th><?php echo _l('cyborg_push_subscription_user'); ?></th>
                                            <th><?php echo _l('cyborg_push_subscription_device'); ?></th>
                                            <th><?php echo _l('cyborg_push_subscription_endpoint'); ?></th>
                                            <th><?php echo _l('cyborg_push_subscription_active'); ?></th>
                                            <th><?php echo _l('cyborg_push_subscription_created'); ?></th>
                                            <th><?php echo _l('options'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subscriptions as $sub): ?>
                                        <tr>
                                            <td><?php echo $sub['id']; ?></td>
                                            <td>
                                                <?php echo $sub['user_name']; ?>
                                                <br><small class="text-muted"><?php echo $sub['user_type']; ?></small>
                                            </td>
                                            <td><?php echo ucfirst($sub['device_type']); ?></td>
                                            <td>
                                                <span title="<?php echo htmlspecialchars($sub['endpoint']); ?>">
                                                    <?php echo substr($sub['endpoint'], 0, 50) . '...'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($sub['is_active']): ?>
                                                    <span class="label label-success"><?php echo _l('is_active_export'); ?></span>
                                                <?php else: ?>
                                                    <span class="label label-danger"><?php echo _l('is_not_active_export'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo _dt($sub['created_at']); ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('cyborg_push/delete_subscription/' . $sub['id']); ?>" 
                                                   class="btn btn-danger btn-xs _delete">
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
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
