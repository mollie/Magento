import { expect, test } from '@playwright/test';
import { expectOrderAuthorizedByWebhook, expectOrderPaidByWebhook, expectSuccessPageForOrder } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { createCustomer } from '../support/customer';
import { cancelOrder, getMolliePaymentStatus, shipOrder } from '../support/magento';
import { completeTestPaymentWithoutReturning } from '../support/mollie';

test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

test('a Riverty payment is authorized at checkout and captured when the order ships', async ({ page }) => {
  const customer = createCustomer('NL');

  await addProductToCart(page, SIMPLE_PRODUCT);
  await placeGuestOrder(page, customer, 'mollie_riverty');
  const returnUrl = await completeTestPaymentWithoutReturning(page, 'authorized');
  const authorizedOrder = await expectOrderAuthorizedByWebhook(customer.email);
  await expectSuccessPageForOrder(page, returnUrl, authorizedOrder.incrementId);
  expect(getMolliePaymentStatus(authorizedOrder.incrementId)).toBe('authorized');

  shipOrder(authorizedOrder.incrementId);

  await expectOrderPaidByWebhook(customer.email, 'complete');
  expect(getMolliePaymentStatus(authorizedOrder.incrementId)).toBe('paid');
});

test('cancelling an authorized Riverty order releases the authorization', async ({ page }) => {
  const customer = createCustomer('NL');

  await addProductToCart(page, SIMPLE_PRODUCT);
  await placeGuestOrder(page, customer, 'mollie_riverty');
  await completeTestPaymentWithoutReturning(page, 'authorized');
  const authorizedOrder = await expectOrderAuthorizedByWebhook(customer.email);

  cancelOrder(authorizedOrder.incrementId);

  await expect
    .poll(() => getMolliePaymentStatus(authorizedOrder.incrementId), { timeout: 30_000 })
    .toBe('canceled');
});
