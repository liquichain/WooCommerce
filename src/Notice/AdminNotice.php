<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Notice;

class AdminNotice implements NoticeInterface
{
    public function addNotice($level, $message)
    {
        add_action(
            'admin_notices',
            static function () use ($level, $message) {
                ?>
                    <div class="notice <?= esc_attr($level) ?>" style="padding:12px 12px">
                        <?= wp_kses_post($message) ?>
                    </div>
                    <?php
            }
        );
    }
}
