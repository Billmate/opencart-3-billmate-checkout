<?php

class ModelBillmateAdminService extends Model
{
    public function addInvoiceIdToOrder($orderId, $invoiceId = null)
	{
	    if (!$invoiceId) {
	    	return;
	    }

        $this->createInvoiceTable();

        $this->db->query(
            'INSERT INTO ' . DB_PREFIX . 'billmate_order_invoice (`order_id`, `invoice_id`)
            VALUES (' . (int)$orderId . ',"' . $this->db->escape($invoiceId) . '")
            ON DUPLICATE KEY UPDATE `invoice_id` = "' . $this->db->escape($invoiceId) . '"'
        );
	}

	public function getInvoiceId($orderId)
	{
        $query = $this->db->query("SELECT `invoice_id` FROM `" . DB_PREFIX . "billmate_order_invoice` WHERE order_id = '" . (int)$orderId . "' LIMIT 1");

        if(!$result = $query->row) {
            return false;
        }

        return $result['invoice_id'];
	}

	protected function createInvoiceTable()
    {
        $this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "billmate_order_invoice` (
		  `bm_invoice_id` int(11) NOT NULL AUTO_INCREMENT,
		  `order_id` int(11) NOT NULL ,
		  `invoice_id` varchar(255) NOT NULL ,
		  PRIMARY KEY (`bm_invoice_id`),
		  UNIQUE (`order_id`)
		);");
    }
}
