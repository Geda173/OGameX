<div id="fleetsgenericpage">
    <div id="messagecontainercomponent">
    <!-- TODO: implement trash -->
    <!--<ul class="tab_inner ctn_with_trash clearfix"> -->
    <ul class="tab_inner clearfix">
        <ul class="pagination">
            <li class="paginator" data-tab="20" data-page="1">|&lt;&lt;</li>
            <li class="paginator" data-tab="20" data-page="1">&lt;</li>
            <li class="curPage" data-tab="20">1/1</li>
            <li class="paginator" data-tab="20" data-page="1">&gt;</li>
            <li class="paginator" data-tab="20" data-page="1">&gt;&gt;|</li>
        </ul>
        <input type="hidden" name="token"
               value="d99f68937305e0b2c3ff3f059259fcec">
        <?php
            /** @var OGame\GameMessages\Abstracts\GameMessage[] $messages */
        ?>
        <?php if(count($messages) === 0): ?>
            <li class="no_msg">
                There are currently no messages available in this tab
            </li>
            <br>
        <?php endif; ?>
        <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="msg <?php if($message->isUnread()): ?> msg_new <?php endif; ?>" data-msg-id="<?php echo e($message->getId()); ?>">
                <div class="msg_status"></div>
                <div class="msg_head">
                    <?php if($message->getSubtab() === 'combat_reports'): ?>
                        <span class="msg_title middlemark"><?php echo $message->getSubject(); ?></span>
                    <?php else: ?>
                        <span class="msg_title blue_txt"><?php echo $message->getSubject(); ?></span>
                    <?php endif; ?>
                    <span class="fright">
                        <a href="javascript: void(0);" class="fright">
                            <span class="icon_nf icon_refuse js_actionKill tooltip js_hideTipOnMobile tpd-hideOnClickOutside" title=""></span>
                        </a>

                        <span class="msg_date fright"><?php echo e($message->getDateFormatted()); ?></span>
                    </span>
                    <br>
                    <span class="msg_sender_label">From:</span>
                    <span class="msg_sender"><?php echo e($message->getFrom()); ?></span>
                </div>
                <span class="msg_content">
                  <?php echo $message->getBody(); ?>

                </span>
                <message-footer class="msg_actions">
                    <message-footer-actions>
                        <?php echo $message->getFooterActions(); ?>

                    </message-footer-actions>
                    <message-footer-details>
                        <?php echo $message->getFooterDetails(); ?>

                    </message-footer-details>
                </message-footer>
                <script type="text/javascript">
                    initOverlays();
                </script>

            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <ul class="pagination">
            <li class="paginator" data-tab="20" data-page="1">|&lt;&lt;</li>
            <li class="paginator" data-tab="20" data-page="1">&lt;</li>
            <li class="curPage" data-tab="20">1/1</li>
            <li class="paginator" data-tab="20" data-page="1">&gt;</li>
            <li class="paginator" data-tab="20" data-page="1">&gt;&gt;|</li>
        </ul>
    </ul>
    <?php echo $__env->make('ingame.messages.tabs.subtab-init-js', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
</div><?php /**PATH /var/www/resources/views/ingame/messages/tabs/fleets/subtab.blade.php ENDPATH**/ ?>