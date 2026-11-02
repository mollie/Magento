import { expect, test } from '@playwright/test';
import { expectOrderAuthorizedByWebhook, expectOrderPaidByWebhook, expectSuccessPageForOrder } from '../support/assertions';
import { restockProduct, SIMPLE_PRODUCT } from '../support/catalog';
import { addProductToCart, placeGuestOrder } from '../support/checkout';
import { createCustomer } from '../support/customer';
import { cancelOrder, getMolliePaymentStatus, shipOrder } from '../support/magento';
import { completeTestPaymentWithoutReturning } from '../support/mollie';

type ManualCaptureMethod = {
  readonly name: string;
  readonly code: string;
};

const MANUAL_CAPTURE_METHODS: readonly ManualCaptureMethod[] = [
  { name: 'Riverty', code: 'mollie_riverty' },
  { name: 'Billink', code: 'mollie_billink' },
];

test.beforeEach(() => restockProduct(SIMPLE_PRODUCT));

for (const method of MANUAL_CAPTURE_METHODS) {
  test(`a ${method.name} payment is authorized at checkout and captured when the order ships`, async ({ page }) => {
    const customer = createCustomer('NL');

    await addProductToCart(page, SIMPLE_PRODUCT);
    await placeGuestOrder(page, customer, method.code);
    const returnUrl = await completeTestPaymentWithoutReturning(page, 'authorized');
    const authorizedOrder = await expectOrderAuthorizedByWebhook(customer.email);
    await expectSuccessPageForOrder(page, returnUrl, authorizedOrder.incrementId);
    expect(getMolliePaymentStatus(authorizedOrder.incrementId)).toBe('authorized');

    shipOrder(authorizedOrder.incrementId);

    await expectOrderPaidByWebhook(customer.email, 'complete');
    expect(getMolliePaymentStatus(authorizedOrder.incrementId)).toBe('paid');
  });

  test(`cancelling an authorized ${method.name} order releases the authorization`, async ({ page }) => {
    const customer = createCustomer('NL');

    await addProductToCart(page, SIMPLE_PRODUCT);
    await placeGuestOrder(page, customer, method.code);
    await completeTestPaymentWithoutReturning(page, 'authorized');
    const authorizedOrder = await expectOrderAuthorizedByWebhook(customer.email);

    cancelOrder(authorizedOrder.incrementId);

    await expect
      .poll(() => getMolliePaymentStatus(authorizedOrder.incrementId), { timeout: 30_000 })
      .toBe('canceled');
  });
}
