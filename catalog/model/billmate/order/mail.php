<?php

/**
 * Class ModelBillmateOrderMail
 */
class ModelBillmateOrderMail extends Model
{
    const EMPTY_COMMENT = '';

    const NOTIFY_FLAG = true;

    /**
     * @param $orderInfo
     */
    public function sendConfirmation($orderInfo)
    {
        $arguments = $this->getArguments($orderInfo);
        $mailAction = new Action('mail/order/add');
        $mailAction->execute(
            $this->registry,
            $arguments
        );
    }

    /**
     * @param $orderInfo
     */
    public function sendUpdate($orderInfo)
    {
        $arguments = $this->getArguments($orderInfo);
        $mailAction = new Action('mail/order/edit');
        $mailAction->execute(
            $this->registry,
            $arguments
        );
    }

    /**
     * @param $orderInfo
     *
     * @return array
     */
    protected function getArguments($orderInfo)
    {
        return [
            $orderInfo,
            $orderInfo['order_status_id'],
            self::EMPTY_COMMENT,
            self::NOTIFY_FLAG
        ];
    }
}