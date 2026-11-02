import { test } from '@playwright/test';
import { expectOrderPaidByWebhook, expectSuccessPageAfterReturning } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { createDutchCustomer } from '../support/customer';
import { completeTestPaymentWithoutReturning, ING_ISSUER, selectIdealIssuer } from '../support/mollie';

test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

test('a paid iDEAL payment is processed by the webhook and shows the success page', async ({ page }) => {
  const customer = createDutchCustomer();

  await addProductToCart(page, SIMPLE_PRODUCT);
  await placeGuestOrder(page, customer, 'mollie_ideal');
  await selectIdealIssuer(page, ING_ISSUER);
  const returnUrl = await completeTestPaymentWithoutReturning(page, 'paid');

  const paidOrder = await expectOrderPaidByWebhook(customer.email);
  await expectSuccessPageAfterReturning(page, returnUrl, paidOrder, customer.email);
});
