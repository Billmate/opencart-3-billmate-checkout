<?php

/**
 * Class ModelBillmateOrderMail
 */
class ModelBillmateOrderMail extends Model
{
    /**
     * @param $orderInfo
     */
    public function sendConfirmation($orderInfo)
    {
        $arguments = [
            $orderInfo,
            $orderInfo['order_status_id'],
            '',
            true
        ];

        $mailAction = new Action('mail/order/add');
        $mailAction->execute($this->registry, $arguments);
    }

    /**
     * @param $orderInfo
     */
    public function sendUpdateStatus($orderInfo)
    {
        $args = [
            $orderInfo,
            $orderInfo['order_status_id'],
            '',
            true
        ];

        //$someResp = $this->load->controller('mail/order');
        $mailAction = new Action('mail/order/edit');
        $mailAction->execute($this->registry, $args);
    }
}