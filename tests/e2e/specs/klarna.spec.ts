import { expect, test } from '@playwright/test';
import { expectOrderPaidByWebhook, expectSuccessPageAfterReturning } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { getConfig, setConfig } from '../support/config';
import { createCustomer } from '../support/customer';
import { completeTestPaymentWithoutReturning } from '../support/mollie';

test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

test('a Klarna payment with automatic capture is processed by the webhook and shows the success page', async ({ page }) => {
  const customer = createCustomer('NL');

  await addProductToCart(page, SIMPLE_PRODUCT);
  await placeGuestOrder(page, customer, 'mollie_klarna');
  const returnUrl = await completeTestPaymentWithoutReturning(page, 'paid');

  const paidOrder = await expectOrderPaidByWebhook(customer.email);
  await expectSuccessPageAfterReturning(page, returnUrl, paidOrder, customer.email);
});

test.describe('with a payment fee', () => {
  const SURCHARGE_SETTING = 'payment/mollie_klarna/payment_surcharge';
  let originalSurcharge: string | null = null;

  test.beforeEach(() => {
    originalSurcharge = getConfig(SURCHARGE_SETTING);
    setConfig(SURCHARGE_SETTING, '1.95');
  });

  test.afterEach(() => setConfig(SURCHARGE_SETTING, originalSurcharge));

  test('a Klarna payment includes the payment fee', async ({ page }) => {
    const customer = createCustomer('NL');

    await addProductToCart(page, SIMPLE_PRODUCT);
    await placeGuestOrder(page, customer, 'mollie_klarna');
    const returnUrl = await completeTestPaymentWithoutReturning(page, 'paid');

    const paidOrder = await expectOrderPaidByWebhook(customer.email);
    expect(paidOrder.paymentFee).toBe('1.9500');
    expect(paidOrder.grandTotal).toBe('301.9500');
    await expectSuccessPageAfterReturning(page, returnUrl, paidOrder, customer.email);
  });
});
