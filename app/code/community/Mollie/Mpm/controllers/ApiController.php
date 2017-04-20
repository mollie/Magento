<?php

/**
 * Copyright (c) 2012-2014, Mollie B.V.
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
 * @copyright   Copyright (c) 2012-2014 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 **/

class Mollie_Mpm_ApiController extends Mage_Core_Controller_Front_Action
{
	/**
	 * @var Mollie_Mpm_Helper_Api
	 */
	protected $_api;

	/**
	 * @var Mollie_Mpm_Model_Api
	 */
	protected $_model;

	/**
	 * Get Mollie core
	 */
	public function _construct ()
	{
		$this->_api   = Mage::helper('mpm/api');
		$this->_model = Mage::getModel('mpm/api');

		parent::_construct();
	}

	/**
	 * @param string      $e        Exception message
	 * @param string|NULL $order_id An OrderID
	 */
	protected function _showException ($e = '', $order_id = NULL)
	{
		$this->loadLayout();

		$block = $this->getLayout()->createBlock('Mage_Core_Block_Template')->setTemplate('mollie/page/exception.phtml')->setData('exception', $e)->setData('orderId', $order_id);

		$this->getLayout()->getBlock('content')->append($block);
		$this->renderLayout();
	}

	/**
	 * Gets the current checkout session with order information
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckout ()
	{
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get the amount of the order in cents, make sure that we return the right value even if the locale is set to
	 * something different than the default (e.g. nl_NL).
	 *
	 * @param Mage_Sales_Model_Order $order
	 *
	 * @return int
	 */
	protected function getAmount (Mage_Sales_Model_Order $order)
	{
		if ($order->getBaseCurrencyCode() === 'EUR')
		{
			$grand_total = $order->getBaseGrandTotal();
		}
		elseif ($order->getOrderCurrencyCode() === 'EUR')
		{
			$grand_total = $order->getGrandTotal();
		}
		else
		{
			Mage::log(__METHOD__ . ' said: Neither Base nor Order currency is in Euros.');
			Mage::throwException(__METHOD__ . ' said: Neither Base nor Order currency is in Euros.');
		}

		if (is_string($grand_total))
		{
			$locale_info = localeconv();

			if ($locale_info['decimal_point'] !== '.' )
			{
				$grand_total = strtr($grand_total, array(
					$locale_info['thousands_sep'] => '',
					$locale_info['decimal_point'] => '.',
				));
			}

			$grand_total = floatval($grand_total); // Why U NO work with locales?
		}

		return floatval(round($grand_total, 2));
	}

	/**
	 * Redirects
	 */
	public function profilesAction ()
	{
		$this->_redirectUrl('https://www.mollie.nl/beheer/account/profielen/');
	}
	public function dashboardAction ()
	{
		$this->_redirectUrl('https://www.mollie.nl/beheer');
	}

	/**
	 * After clicking 'Place Order' the method 'getOrderPlaceRedirectUrl()' gets called and redirects to here
	 * Then this action creates an payment with a transaction_id that gets inserted in the database (mollie_payments, sales_payment_transaction)
	 */
	public function paymentAction ()
	{
		if ($this->getRequest()->getParam('order_id'))
		{
			// Load failed payment order
			/** @var $order Mage_Sales_Model_Order */
			$order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));

			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->__(Mollie_Mpm_Model_Api::PAYMENT_FLAG_RETRY), FALSE)->save();
		}
		else
		{
			// Load last order by IncrementId
			/** @var $order Mage_Sales_Model_Order */
			$orderIncrementId = $this->_getCheckout()->getLastRealOrderId();
			$order            = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		}

		$order_id = $order->getId();

		try
		{
			// Magento is so awesome, we need to manually remember the quote
			$session = $this->_getCheckout();

			if (is_object($session))
			{
				$session->setMollieQuoteId($session->getQuoteId());
			}

			$store_code  = Mage::app()->getStore()->getCode();
			$store_url   = Mage::app()->getStore($store_code)->getBaseUrl();

			// Assign required value's
			$amount       = $this->getAmount($order);
			$description  = str_replace('%', $order->getIncrementId(), Mage::helper('mpm/data')->getConfig('mollie', 'description'));
			$redirect_url = str_replace('/admin/', $store_url, Mage::getUrl('mpm/api/return') . '?order_id=' . intval($order_id) . '&utm_nooverride=1');
			$method       = $this->getRequest()->getParam('method_id', NULL);
			$issuer       = $this->getRequest()->getParam('issuer', NULL);

			if ($this->_api->createPayment($amount, $description, $order, $redirect_url, $method, $issuer))
			{
				if (!$order->getId())
				{
					Mage::log('Geen order voor verwerking gevonden');
					Mage::throwException('Geen order voor verwerking gevonden');
				}

				$this->_model->setPayment($order_id, $this->_api->getTransactionId());

				// Creates transaction
				/** @var $payment Mage_Sales_Model_Order_Payment */
				$payment = Mage::getModel('sales/order_payment')->setMethod('Mollie')->setTransactionId($this->_api->getTransactionId())->setIsTransactionClosed(FALSE);

				$order->setPayment($payment);
				$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->__(Mollie_Mpm_Model_Api::PAYMENT_FLAG_INPROGRESS), FALSE)->save();

				Mage::getSingleton('core/session')->setRestoreCart(true);

				$this->_redirectUrl($this->_api->getPaymentURL());
			}
			else
			{
				Mage::throwException($this->_api->getErrorMessage());
			}
		}
		catch (Exception $e)
		{
			$this->_restoreCart();
			Mage::log($e);
			$this->_showException($e->getMessage(), $order_id);
		}
	}

	/**
	 * This action is getting called by Mollie to report the payment status
	 */
	public function webhookAction ()
	{
		// Determine if this is a connection test
		if ($this->getRequest()->getParam('testByMollie'))
		{
			return;
		}

		// Get transaction_id from post parameter
		$transactionId = $this->getRequest()->getParam('id');

		// Get order by transaction_id
		$orderId = Mage::helper('mpm')->getOrderIdByTransactionId($transactionId);

		// Load order by id ($orderId)
		/** @var $order Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order')->load($orderId);

		try
		{
			if (!empty($transactionId) && $order->getData('status') === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
			{
				if (!$this->_api->checkPayment($transactionId))
				{
					Mage::throwException($this->_api->getErrorMessage());
				}

				$customer = $this->_api->getConsumerInfo();

				// Maakt een Order transactie aan
				/** @var $payment Mage_Sales_Model_Order_Payment */
				$payment = Mage::getModel('sales/order_payment')->setMethod('Mollie')->setTransactionId($transactionId)->setIsTransactionClosed(TRUE);
				$order->setPayment($payment);

				if ($this->_api->getPaidStatus())
				{
					// Als de vorige betaling was mislukt, zijn de producten 'Canceled'... Undo that
					foreach ($order->getAllItems() as $item)
					{
						/** @var $item Mage_Sales_Model_Order_Item */
						$item->setQtyCanceled(0);
						$item->save();
					}

					$this->_model->updatePayment($transactionId, $this->_api->getBankStatus(), $customer);

					/*
					 * Send an email to the customer.
					 */
					if (!Mage::helper('mpm')->getConfig('mollie', 'skip_order_mails'))
					{
						$order->sendNewOrderEmail()->setEmailSent(TRUE);
					}

					if ($transaction = $payment->getTransaction($transactionId))
					{
						$transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
						$transaction->setIsClosed(TRUE);
						$transaction->save();
					}
					else
					{
						Mage::log(__METHOD__ . ' said: Could not find a transaction with id ' . $transactionId . ' for order ' . $orderId);

						return;
					}

					if (Mage::helper('mpm')->getConfig('mollie', 'skip_invoice') || ! $order->canInvoice())
					{
						/*
						 * Update the total amount paid
						 */
						try
						{
							$amount       = $this->_api->getAmount(); // Amount in EUROs
							$curr_base    = Mage::app()->getStore()->getBaseCurrencyCode();
							$curr_store   = Mage::app()->getStore()->getCurrentCurrencyCode();
							$amount_base  = Mage::helper('directory')->currencyConvert($amount, 'EUR', $curr_base);
							$amount_store = Mage::helper('directory')->currencyConvert($amount, 'EUR', $curr_store);

							$order->setBaseTotalPaid($amount_base);
							$order->setTotalPaid($amount_store);
						}
						catch (Exception $e)
						{
							Mage::log(__METHOD__ . '() said: Could not convert currencies. Details: ' . $e->getMessage());
						}
					}
					else
					{
						$this->_savePaidInvoice($order, $transaction->getId());
					}

					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING, $this->__(Mollie_Mpm_Model_Api::PAYMENT_FLAG_PROCESSED ), TRUE);
					$order->save();
				}
				else
				{
					$this->_model->updatePayment($transactionId, $this->_api->getBankStatus());
					// Stomme Magento moet eerst op 'cancel' en dan pas setState, andersom dan zet hij de voorraad niet terug.
					$order->cancel();
					$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $this->__(Mollie_Mpm_Model_Api::PAYMENT_FLAG_CANCELD ), FALSE)->save();
				}
			}
		}
		catch (Exception $e)
		{
			Mage::log($e);
			$this->_showException($e->getMessage());
		}
	}

	/**
	 * Save an invoice for the order.
	 *
	 * @param Mage_Sales_Model_Order $order
	 * @param null                   $transaction_id
	 *
	 * @return bool
	 */
	protected function _savePaidInvoice (Mage_Sales_Model_Order $order, $transaction_id = NULL)
	{
		$invoice = $order->prepareInvoice()->register()->setTransactionId($transaction_id)->pay();

		Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();

		if (!Mage::helper('mpm')->getConfig('mollie', 'skip_invoice_mails'))
		{
			$invoice->sendEmail();
		}

		return TRUE;
	}


	/**
	 * Customer returning from the bank with an order_id
	 * Depending on what the state of the payment is they get redirected to the corresponding page
	 */
	public function returnAction ()
	{
		// Unset the restore cart session when returned properly (used for the cart restore when clicking the browser's back button during a payment).
		if(Mage::getSingleton('core/session')->getRestoreCart()) {
			Mage::getSingleton('core/session')->unsRestoreCart();
		}

		// Clear the cart just in case it has been restored by accident (e.g: user refreshes Magento in a seperate tab while still in payment screen).
		$this->_clearCart();

		// Get order_id and transaction_id from url (Ex: http://yourmagento.com/index.php/api/return?order_id=123 )
		$orderId       = $this->getRequest()->getParam('order_id');
		$transactionId = Mage::helper('mpm')->getTransactionIdByOrderId($orderId);

		try
		{
			if (!empty($transactionId))
			{
				// Get payment status from database ( `mollie_payments` )
				$oStatus = Mage::helper('mpm')->getStatusById($transactionId);

				if (empty($oStatus['updated_at'] ) || $oStatus['bank_status'] === Mollie_Mpm_Model_Api::STATUS_PAID || $oStatus['bank_status'] === Mollie_Mpm_Model_Api::STATUS_PENDING)
				{
					if (empty($oStatus['updated_at']))
					{
						/*
						 * Send an email to the customer.
						 */
						if (!Mage::helper('mpm')->getConfig('mollie', 'skip_order_mails'))
						{
							// Load order by id ($orderId)
							/** @var $order Mage_Sales_Model_Order */
							$order = Mage::getModel('sales/order')->load($orderId);
							$order->sendNewOrderEmail()->setEmailSent(TRUE);
						}
					}

					if ($this->_getCheckout()->getQuote()->items_count > 0)
					{
						// Empty the shopping cart if it didn't clear itself
						foreach ($this->_getCheckout()->getQuote()->getItemsCollection() as $item)
						{
							Mage::getSingleton('checkout/cart')->removeItem($item->getId());
						}

						Mage::getSingleton('checkout/cart')->save();
					}

					// Redirect to success page
					$this->_redirect('checkout/onepage/success', array('_secure' => TRUE));

					return;
				}
				else
				{
					$this->_restoreCart();

					// Redirect to cart
					$this->_redirect('checkout/onepage/failure', array('_secure' => TRUE));

					return;
				}
			}
		}
		catch (Exception $e)
		{
			$this->_restoreCart();
			Mage::log($e);
			$this->_showException($e->getMessage(), $orderId);

			return;
		}

		$this->_redirectUrl(Mage::getBaseUrl());
	}

	/**
	 * Put items back in the shopping cart
	 */
	protected function _restoreCart ()
	{
		$session = $this->_getCheckout();

		if (is_object($session) && $quoteId = $session->getMollieQuoteId())
		{
			$quote = Mage::getModel('sales/quote')->load($quoteId);

			if ($quote->getId())
			{
				$quote->setIsActive(TRUE)->save();
				$session->setQuoteId($quoteId);
			}
		}
	}

	/**
	 * Clear the cart
	 */
	protected function _clearCart ()
	{
		$session = $this->_getCheckout();

		if (is_object($session) && $quoteId = $session->getMollieQuoteId())
		{
			$quote = Mage::getModel('sales/quote')->load($quoteId);

			if ($quote->getId())
			{
				$quote->setIsActive(false)->save();
			}
    	}
	}
}
