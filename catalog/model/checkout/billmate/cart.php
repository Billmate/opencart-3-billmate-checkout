<?php
class ModelExtensionCheckoutBillmate extends Model
{
    public function clearCustomCart($session_id)
    {
       $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE session_id = '" . $this->db->escape($session_id) . "'");
       $this->db->query("DELETE FROM " . DB_PREFIX . "cart_session WHERE session_id = '" . $this->db->escape($session_id) . "'");

       return true;
    }
}
