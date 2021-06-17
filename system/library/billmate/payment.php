<?php

namespace Billmate;

class Payment
{
    private $number;

    public function toJson()
    {
        return json_encode($this->build());
    }

    public function build()
    {
        return [
            'number' => $this->number,
        ];
    }

    public function addNumber($value)
    {
        $this->number = $value;
    }
}
