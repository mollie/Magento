import { expect, test } from '@playwright/test';
import { expectOrderPaidByWebhook, expectSuccessPageAfterReturning } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { getConfig, setConfig } from '../support/config';
import { createCustomer } from '../support/customer';
import { completeTestPaymentWithoutReturning } from '../support/mollie';
import { findLatestOrderByEmail } from '../support/orders';

const METHOD_CODE = 'mollie_bancontact';
const API_SETTING = `payment/${METHOD_CODE}/method`;

let originalApiSetting: string | null = null;

test.beforeEach(() => {
  restockProduct(SIMPLE_PRODUCT);
  originalApiSetting = getConfig(API_SETTING);
});

test.afterEach(() => setConfig(API_SETTING, originalApiSetting));

test('an Orders API payment is still processed after switching the method to the Payments API', async ({ page }) => {
  const customer = createCustomer('NL');
  setConfig(API_SETTING, 'order');

  await addProductToCart(page, SIMPLE_PRODUCT);
  await placeGuestOrder(page, customer, METHOD_CODE);
  await page.waitForURL(/www\.mollie\.com/);
  expect(findLatestOrderByEmail(customer.email)?.mollieTransactionId).toMatch(/^ord_/);
  setConfig(API_SETTING, 'payment');
  const returnUrl = await completeTestPaymentWithoutReturning(page, 'paid');

  const paidOrder = await expectOrderPaidByWebhook(customer.email);
  await expectSuccessPageAfterReturning(page, returnUrl, paidOrder, customer.email);
});
