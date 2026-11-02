import { expect, Page } from '@playwright/test';
import { findLatestOrderByEmail, PlacedOrder } from './orders';

export const expectOrderPaidByWebhook = async (email: string): Promise<PlacedOrder> => {
  await expect
    .poll(() => findLatestOrderByEmail(email)?.invoiceCount, {
      message: 'the webhook created the invoice while the customer was still at Mollie',
      timeout: 60_000,
    })
    .toBe(1);

  const order = findLatestOrderByEmail(email);
  expect(order?.state).toBe('processing');
  expect(order?.totalPaid).toBe(order?.grandTotal);

  return order as PlacedOrder;
};

export const expectSuccessPageAfterReturning = async (
  page: Page,
  returnUrl: string,
  paidOrder: PlacedOrder,
  email: string,
): Promise<void> => {
  expect(new URL(returnUrl).pathname).toContain('/mpm/api/return');

  await page.goto(returnUrl);

  await expect(page).toHaveURL(/checkout\/onepage\/success/);
  await expect(page.locator('body')).toContainText(`Your order # is: ${paidOrder.incrementId}`);
  const orderAfterReturn = findLatestOrderByEmail(email);
  expect(orderAfterReturn?.totalPaid).toBe(paidOrder.grandTotal);
  expect(orderAfterReturn?.invoiceCount).toBe(1);
};
