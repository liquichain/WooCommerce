<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\Notice;

/**
 * Interface NoticeInterface
 *
 * @package Liquichain\WC\Notice
 */
interface NoticeInterface
{

    /**
     * @param string $level class to apply: ex. 'notice-error'
     * @param string $message translated message
     *
     * @return mixed
     */
    public function addNotice($level, $message);
}
