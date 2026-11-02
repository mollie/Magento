import { test } from '@playwright/test';
import { expectOrderPaidByWebhook, expectSuccessPageAfterReturning } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
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
