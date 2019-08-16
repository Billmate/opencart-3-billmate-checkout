<?php
namespace Billmate;
use Cart\Cart;

class Bmcart extends Cart
{
    const CART_PREFIX = 0;

    /**
     * @param $sessionId
     *
     * @return mixed
     */
    public function clearBySession($sessionId)
    {
        $this->clearCartBySession($sessionId);
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

    /**
     * @param $sessionId
     *
     * @return mixed
     */
    public function getCartIdentifier($sessionId)
    {
        $this->addCartSession($sessionId);
        $sessionCartId = $this->getSessionCartId($sessionId);
        return $this->addPrefix($sessionCartId);
    }

    /**
     * @param $sessCartId
     *
     * @return mixed
     */
    public function addPrefix($sessCartId)
    {
        return self::CART_PREFIX + $sessCartId;
    }

    /**
     * @param $sessCartIdPref
     *
     * @return mixed
     */
    public function clearPrefix($sessCartIdPref)
    {
        return $sessCartIdPref - self::CART_PREFIX;
    }

    /**
     * @param $sessionId
     */
    public function addCartSession($sessionId)
    {
        if ($sessionId) {
            $this->createCartSessionTable();
            $this->db->query(
                'INSERT INTO `' . DB_PREFIX . 'cart_session` (`session_id`)
                VALUES ( "' . $this->db->escape($sessionId) . '")
                ON DUPLICATE KEY UPDATE `session_id` = "' . $this->db->escape($sessionId) . '"'
            );
        }
    }

    /**
     * @param $sessionId
     *
     * @return int|false
     */
    public function getSessionCartId($sessionId)
    {
        $query = $this->db->query(
            "SELECT cs.session_cart_id FROM " . DB_PREFIX . "cart_session cs WHERE session_id = '" . $this->db->escape($sessionId) . "'"
        );

        $result = $query->row;
        if($result) {
            return $result['session_cart_id'];
        }

        return false;
    }

    /**
     * @param $sessionCartId
     *
     * @return int|false
     */
    public function getSessionByCartId($sessionCartId)
    {
        $cartId = $this->clearPrefix($sessionCartId);
        $query =  $this->db->query(
            "SELECT cs.session_id FROM "
            . DB_PREFIX . "cart_session cs WHERE session_cart_id = '"
            . $this->db->escape($cartId) . "'"
        );

        $result = $query->row;
        if($result) {
            return $result['session_id'];
        }
    }

    /**
     * @param $sessionId
     *
     * @return mixed
     */
    public function clearCartBySession($sessionId)
    {
        return $this->db->query("DELETE FROM " . DB_PREFIX . "cart_session WHERE session_id = '" . $this->db->escape($sessionId) . "'");
    }

    protected function createCartSessionTable()
    {
        $this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cart_session` (
		  `session_cart_id` int(11) NOT NULL AUTO_INCREMENT,
		  `session_id` varchar(255) NOT NULL ,
		  PRIMARY KEY (`session_cart_id`),
		  UNIQUE (`session_id`)
		);");
    }
}