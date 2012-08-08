<?php

/**
 * Copyright (c) 2012, Mollie B.V.
 * All rights reserved. 
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met: 
 * 
 * - Redistributions of source code must retain the above copyright notice, 
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright 
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY 
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR 
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY 
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH 
 * DAMAGE. 
 *
 * @category    Mollie
 * @package     Mollie_Mpm
 * @author      Mollie B.V. (info@mollie.nl)
 * @version     v3.5.0
 * @copyright   Copyright (c) 2012 Mollie B.V. (http://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 * 
 **/

class Mollie_Mpm_IdlController extends Mage_Core_Controller_Front_Action
{

	/**
	 * @var Mollie_Mpm_Helper_Idl
	 */
	protected $_ideal;

	/**
	 * @var Mollie_Mpm_Model_Idl
	 */
	protected $_model;

	/**
	 * Get iDEAL core
	 * Give $_write mage writing resource
	 * Give $_read mage reading resource
	 */
	public function _construct ()
	{
		$this->_ideal = Mage::Helper('mpm/idl');
		$this->_model = Mage::getModel('mpm/idl');
		parent::_construct();
	}

	protected function _showException ($e = '', $order_id = NULL)
	{
		$this->loadLayout();

		$block = $this->getLayout()
				->createBlock('Mage_Core_Block_Template')
				->setTemplate('mollie/page/exception.phtml')
				->setData('exception', $e)
				->setData('orderId', $order_id);

		$this->getLayout()->getBlock('content')->append($block);
		$this->renderLayout();
	}

	/**
	 * Gets the current checkout session with order information
	 * 
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get the amount of the order in cents, make sure that we return the right value even if the locale is set to
	 * something different than the default (e.g. nl_NL).
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @return int
	 */
	protected function getAmountInCents (Mage_Sales_Model_Order $order)
	{
		$grand_total = $order->getGrandTotal();

		if (is_string($grand_total))
		{
			$locale_info = localeconv();

			if ($locale_info['decimal_point'] !== '.')
			{
				$grand_total = strtr($grand_total, array(
					$locale_info['thousands_sep'] => '',
					$locale_info['decimal_point'] => '.',
				));
			}

			$grand_total = floatval($grand_total); // Why U NO work with locales?
		}

		return intval(round(100 * $grand_total));
	}

	/**
	 * After clicking 'Place Order' the method 'getOrderPlaceRedirectUrl()' gets called and redirects to here with the bank_id
	 * Then this action creates an payment with a transaction_id that gets inserted in the database (mollie_payments, sales_payment_transaction)
	 */
	public function paymentAction ()
	{
		if ($this->getRequest()->getParam('order_id')) {
			// Load failed payment order
			/** @var $order Mage_Sales_Model_Order */
			$order = Mage::getModel('sales/order')->loadByIncrementId($this->getRequest()->getParam('order_id'));
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->__(Mollie_Mpm_Model_Idl::PAYMENT_FLAG_RETRY), false)->save();
		} else {
			// Load last order
			/** @var $order Mage_Sales_Model_Order */
			$order = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckout()->last_real_order_id);
		}

		try
		{
			// Assign required value's
			$bank_id     = $this->getRequest()->getParam('bank_id');
			$amount      = $this->getAmountInCents($order);
			$description = str_replace('%', $order->getIncrementId(), Mage::Helper('mpm/data')->getConfig('idl', 'description'));
			$return_url  = Mage::getUrl('mpm/idl/return');
			$report_url  = Mage::getUrl('mpm/idl/report');

			if ($amount < Mage::Helper('mpm/data')->getConfig('idl', 'minvalue')) {
				Mage::throwException(
					sprintf(
						"Order bedrag (%s centen) is lager dan ingesteld (%s centen)",
						$amount,
						Mage::Helper('mpm/data')->getConfig('idl', 'minvalue')
					)
				);
			}

			if ($this->_ideal->createPayment($bank_id, $amount, $description, $return_url, $report_url))
			{
				if (!$order->getId()) {
					Mage::log('Geen order voor verwerking gevonden');
					Mage::throwException('Geen order voor verwerking gevonden');
				}

				$this->_model->setPayment($order->getIncrementId(), $this->_ideal->getTransactionId());

				// Creates transaction
				/** @var $payment Mage_Sales_Model_Order_Payment */
				$payment = Mage::getModel('sales/order_payment')
									->setMethod('iDEAL')
									->setTransactionId($this->_ideal->getTransactionId())
									->setIsTransactionClosed(false);


				$order->setPayment($payment);

				$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->__(Mollie_Mpm_Model_Idl::PAYMENT_FLAG_INPROGRESS), false)->save();

				$this->_redirectUrl($this->_ideal->getBankURL());
			}
			else
			{
				Mage::throwException($this->_ideal->getErrorMessage());
			}
		}
		catch (Exception $e)
		{
			Mage::log($e);
			$this->_showException($e->getMessage(), $order->getId());
		}
	}

	/**
	 * This action is getting called by Mollie to report the payment status
	 */
	public function reportAction ()
	{
		// Get transaction_id from url (Ex: http://yourmagento.com/index.php/idl/report?transaction_id=0144ba13aa6dec410a80d5ed4fb60054 )
		$transactionId = $this->getRequest()->getParam('transaction_id');

		// Get order by transaction_id
		$orderId = Mage::helper('mpm/data')->getOrderIdByTransactionId($transactionId);

		// Load order by id ($oId)
		/** @var $order Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

		try
		{
			if (!empty($transactionId) && $order->getData('status') == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
			{
				$this->_ideal->checkPayment($transactionId);

				$customer = $this->_ideal->getConsumerInfo();

				// Maakt een Order transactie aan
				/** @var $payment Mage_Sales_Model_Order_Payment */
				$payment = Mage::getModel('sales/order_payment')
						->setMethod('iDEAL')
						->setTransactionId($transactionId)
						->setIsTransactionClosed(true);

				$order->setPayment($payment);

				if ($this->_ideal->getPaidStatus())
				{
					if ($this->_ideal->getAmount() == $this->getAmountInCents($order))
					{
						// Als de vorige betaling was mislukt dan zijn de producten 'Canceled' die un-canceled worden
						foreach ($order->getAllItems() as $item) {
							/** @var $item Mage_Sales_Model_Order_Item */
							$item->setQtyCanceled(0);
							$item->save();
						}

						$this->_model->updatePayment($transactionId, $this->_ideal->getBankStatus(), $customer);

						$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
						$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING, $this->__(Mollie_Mpm_Model_Idl::PAYMENT_FLAG_PROCESSED), true);

						/*
						 * Send an email to the customer.
						 */
						$order->sendNewOrderEmail()->setEmailSent(true);
					}
					else
					{
						$this->_model->updatePayment($transactionId, $this->_ideal->getBankStatus());
						$order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage_Sales_Model_Order::STATUS_FRAUD, $this->__(Mollie_Mpm_Model_Idl::PAYMENT_FLAG_FRAUD), false);
					}
				}
				else
				{
					$this->_model->updatePayment($transactionId, $this->_ideal->getBankStatus());
					// Stomme Magento moet eerst op 'cancel' en dan pas setState, andersom dan zet hij de voorraad niet terug.
					$order->cancel();
					$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $this->__(Mollie_Mpm_Model_Idl::PAYMENT_FLAG_CANCELD), false);
				}

				$order->save();
			}
		}
		catch (Exception $e)
		{
			Mage::log($e);
			$this->_showException($e->getMessage());
		}
	}

	/**
	 * Customer returning from the bank with an transaction_id
	 * Depending on what the state of the payment is they get redirected to the corresponding page
	 */
	public function returnAction ()
	{
		// Get transaction_id from url (Ex: http://youmagento.com/index.php/idl/return?transaction_id=45r6tuyhijg67u3gds )
		$transactionId = $this->getRequest()->getParam('transaction_id');
		$orderId      = Mage::helper('mpm/data')->getOrderIdByTransactionId($transactionId);

		try
		{
			if (!empty($transactionId))
			{
				// Get payment status from database ( `mollie_payments` )
				$oStatus  = Mage::helper('mpm/data')->getStatusById($transactionId);

				if ($oStatus['bank_status'] == Mollie_Mpm_Model_Idl::IDL_SUCCESS)
				{
					if ($this->_getCheckout()->getQuote()->items_count > 0)
					{
						// Maak winkelwagen leeg
						foreach ($this->_getCheckout()->getQuote()->getItemsCollection() as $item) {
							Mage::getSingleton('checkout/cart')->removeItem($item->getId());
						}
						Mage::getSingleton('checkout/cart')->save();
					}

					// Redirect to success page
					$this->_redirect('checkout/onepage/success', array('_secure' => true));
					return;
				}
				else
				{
					// Create fail page
					$this->loadLayout();

					$block = $this->getLayout()
							->createBlock('Mage_Core_Block_Template')
							->setTemplate('mollie/page/fail.phtml')
							->setData('banks', Mage::Helper('mpm/idl')->getBanks())
							->setData('form', Mage::getUrl('mpm/idl/form'))
							->setData('order', Mage::getModel('sales/order')->loadByIncrementId($orderId));

					$this->getLayout()->getBlock('content')->append($block);

					$this->renderLayout();
					return;
				}
			}
		}
		catch (Exception $e)
		{
			Mage::log($e);
			$this->_showException($e->getMessage(), $orderId);
			return;
		}

		$this->_redirectUrl(Mage::getBaseUrl());
	}

	public function formAction ()
	{
		if ($this->getRequest()->isPost())
		{
			$create_new_payment = Mage::getUrl(
				'mpm/idl/payment',
				array(
					'_secure' => TRUE,
					'_query' => array(
						'bank_id'  => $this->getRequest()->getPost('bank_id'),
						'order_id' => $this->getRequest()->getPost('order_id')
					)
				)
			);

			$this->_redirectUrl($create_new_payment);
		}
	}

}
