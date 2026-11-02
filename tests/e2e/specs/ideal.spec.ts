import { expect, test } from '@playwright/test';
import { addProductToCart, placeGuestOrder, SIMPLE_PRODUCT_URL } from '../support/checkout';
import { createDutchCustomer } from '../support/customer';
import { completeTestPaymentWithoutReturning, ING_ISSUER, selectIdealIssuer } from '../support/mollie';
import { findLatestOrderByEmail } from '../support/orders';

test('a paid iDEAL payment is processed by the webhook and shows the success page', async ({ page }) => {
  const customer = createDutchCustomer();

  await addProductToCart(page, SIMPLE_PRODUCT_URL);
  await placeGuestOrder(page, customer, 'mollie_ideal');
  await selectIdealIssuer(page, ING_ISSUER);
  const returnUrl = await completeTestPaymentWithoutReturning(page, 'paid');

  expect(new URL(returnUrl).pathname).toContain('/mpm/api/return');
  await expect
    .poll(() => findLatestOrderByEmail(customer.email)?.invoiceCount, {
      message: 'the webhook created the invoice while the customer was still at Mollie',
      timeout: 60_000,
    })
    .toBe(1);
  const paidOrder = findLatestOrderByEmail(customer.email);
  expect(paidOrder?.state).toBe('processing');
  expect(paidOrder?.totalPaid).toBe(paidOrder?.grandTotal);

  await page.goto(returnUrl);

  await expect(page).toHaveURL(/checkout\/onepage\/success/);
  await expect(page.locator('body')).toContainText(`Your order # is: ${paidOrder?.incrementId}`);
  const orderAfterReturn = findLatestOrderByEmail(customer.email);
  expect(orderAfterReturn?.totalPaid).toBe(paidOrder?.grandTotal);
  expect(orderAfterReturn?.invoiceCount).toBe(1);
});
