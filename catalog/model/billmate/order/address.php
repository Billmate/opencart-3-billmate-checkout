<?php

/**
 * Class ModelBillmateOrderAddress
 */
class ModelBillmateOrderAddress extends Model
{
    /**
     * @param $iso2Code
     *
     * @return mixed
     */
    public function getCountryByCode($iso2Code) {
        $query = $this->db->query(
            "SELECT DISTINCT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($iso2Code) . "'"
        );

        return $query->row;
    }

}