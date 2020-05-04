<?php
class ModelBillmateCheckoutHandlingInvoiceFee extends Model
{
    /**
     * @var Helperbm
     */
    protected $helperBillmate;

    /**
     * @var array
     */
    protected $defaultFeeData = [
        'fee_without_tax' => 0,
        'fee_tax_rate' => 0,
        'fee_tax_value' => 0,
    ];

    /**
     * ModelBillmateCheckoutHandlingInvoiceFee constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @return array
     */
    public function getData()
    {
        $invoiceFee = $this->getInvoiceFeeAmount();;
        if (!$invoiceFee) {
            return $this->getDefaultFeeData();
        }
        return $this->collectFeeData();
    }

    /**
     * @return array
     */
    public function collectFeeData()
    {
        $invoiceFeeClassId = $this->getHelperBillmate()->getInvoiceFeeTax();
        $invoiceFeeAmount = $this->getInvoiceFeeAmount();
        $rate = 0;
        $taxAmount = 0;
        if ($invoiceFeeClassId) {
            $rates = $this->tax->getRates(
                $invoiceFeeAmount,
                $invoiceFeeClassId
            );
            foreach ($rates as $rateRow) {
                $rate += $rateRow['rate'];
                $taxAmount += $rateRow['amount'];
            }
        }

        $collectedData['fee_without_tax'] = $invoiceFeeAmount;
        $collectedData['fee_tax_rate'] = $rate;
        $collectedData['fee_tax_value'] = $taxAmount;
        return $collectedData;
    }

    /**
     * @return float
     */
    protected function getInvoiceFeeAmount()
    {
        $invoiceFeeAmount = $this->getHelperBillmate()->getInvoiceFee();
        return $this->convert($invoiceFeeAmount);
    }

    /**
     * @param $amount
     *
     * @return float
     */
    protected function convert($amount)
    {
        return $this->currency->format(
            $amount,
            $this->session->data['currency'],
            '',
            false
        );
    }

    /**
     * @return Helperbm
     */
    public function getHelperBillmate()
    {
        return $this->helperBillmate;
    }

    /**
     * @return array
     */
    public function getDefaultFeeData()
    {
        return $this->defaultFeeData;
    }
}