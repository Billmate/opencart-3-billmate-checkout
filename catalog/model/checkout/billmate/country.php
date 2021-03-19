<?php

class ModelCheckoutBillmateCounty extends Model
{
    public function getCountryIdByCode($country_code)
    {
       $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($country_code) . "'");

       return !empty($query->row) ? $query->row['country_id'] : 0;
    }
}
