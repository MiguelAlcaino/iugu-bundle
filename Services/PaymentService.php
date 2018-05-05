<?php
/**
 * Copyright (c) 2018. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

/**
 * Created by PhpStorm.
 * User: malcaino
 * Date: 22/04/18
 * Time: 00:18
 */

namespace MiguelAlcaino\IuguBundle\Services;


use MiguelAlcaino\PaymentGateway\Exception\Charge\CreditCardChargeException;
use MiguelAlcaino\PaymentGateway\Interfaces\Entity\TransactionRecordInterface;
use MiguelAlcaino\PaymentGateway\Interfaces\PaymentGatewayInterface;


class PaymentService implements PaymentGatewayInterface
{
    private $iuguToken;

    private $currency;

    private $businessName;

    private $isPaidInCents;
    
    private $refundStatus;

    /**
     * PaymentService constructor.
     * @param string $iuguToken
     * @param string $currency
     * @param string $businessName
     * @param boolean $isPaidInCents
     */
    public function __construct($iuguToken, $currency, $businessName, $isPaidInCents, $refundStatus)
    {
        $this->iuguToken = $iuguToken;
        $this->currency = $currency;
        $this->businessName = $businessName;
        $this->isPaidInCents = $isPaidInCents;
        $this->refundStatus = $refundStatus;
    }

    /**
     * @param \MiguelAlcaino\PaymentGateway\Interfaces\Entity\TransactionRecordInterface|TransactionRecord $transactionRecord
     * @param \MiguelAlcaino\PaymentGateway\Interfaces\Entity\TransactionItemInterface $transactionItem
     * @param string $creditCardToken
     * @return array|\MiguelAlcaino\PaymentGateway\Interfaces\Entity\TransactionRecordInterface
     * @throws CreditCardChargeException
     */
    public function charge(\MiguelAlcaino\PaymentGateway\Interfaces\Entity\TransactionRecordInterface $transactionRecord, \MiguelAlcaino\PaymentGateway\Interfaces\Entity\TransactionItemInterface $transactionItem, string $creditCardToken)
    {
        \Iugu::setApiKey($this->iuguToken);

        $arrayRequest = [
            'token' => $creditCardToken,
            'email' => $transactionRecord->getCustomer()->getEmail(),
            'items' => [
                'description' => $transactionItem->getName(),
                "quantity" => "1",
                "price_cents" => $this->isPaidInCents ? $transactionItem->getPrice()*100 : $transactionItem->getPrice()
            ]
        ];

        if($transactionRecord->getInstallments() != null || $transactionRecord->getInstallments() != 1){
            $arrayRequest['months'] = $transactionRecord->getInstallments();
        }

        $result = \Iugu_Charge::create($arrayRequest);

        if(!$result->success){
            $exception = new CreditCardChargeException();

            throw $exception;
        }

        $transactionRecord->getTransactionItems()->add($transactionItem);

        $transactionRecord
            ->setPaymentGatewayResponse(json_encode($result))
            ->setStatus($result->message)
            ->setAuthorizationCode($result->invoice_id);

        return [
            'transactionRecord' => $transactionRecord,
            'result' => $result
        ];
    }

    public function refund(TransactionRecordInterface $transactionRecord){
        \Iugu::setApiKey($this->iuguToken);
        $invoiceApi = new \Iugu_Invoice([
            'id' => $transactionRecord->getAuthorizationCode()
        ]);
        $invoiceApi->refund();

        $transactionRecord
            ->setStatus($this->refundStatus)
            ->setRefundDate(new \DateTime());

        return $transactionRecord;
    }
}