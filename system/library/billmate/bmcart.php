<?php
namespace Billmate;
use Cart\Cart;

class Bmcart extends Cart
{
    /**
     * @param $sessionId
     *
     * @return mixed
     */
    public function clearBySession($sessionId)
    {
        return $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE session_id = '" . $this->db->escape($sessionId) . "'");
    }

    /**
     * @param $sessionId
     *
     * @return mixed
     */
    public function getSessionByCartProductId($cartProductId)
    {
        return $this->db->query("SELECT pc.session_id FROM " . DB_PREFIX . "cart pc WHERE cart_id = '" . $this->db->escape($cartProductId) . "'");
    }
}