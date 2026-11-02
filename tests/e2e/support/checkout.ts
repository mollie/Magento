import { Page } from '@playwright/test';
import { Product } from './catalog';
import { Address } from './customer';

const billingField = (page: Page, name: string) => page.locator(`#billing\\:${name}`);

export const addProductToCart = async (page: Page, product: Product): Promise<void> => {
  await page.goto(product.url);
  await page.locator('.add-to-cart-buttons button.btn-cart').first().click();
  await page.waitForURL('**/checkout/cart/');
};

const continueAsGuest = async (page: Page): Promise<void> => {
  await page.goto('/checkout/onepage/');
  await page.locator('#login\\:guest').check();
  await page.locator('#onepage-guest-register-button').click();
};

const fillBillingAddress = async (page: Page, address: Address): Promise<void> => {
  await billingField(page, 'country_id').selectOption(address.countryId);
  if (address.region !== null) {
    await billingField(page, 'region_id').selectOption({ label: address.region });
  }
  await billingField(page, 'firstname').fill(address.firstname);
  await billingField(page, 'lastname').fill(address.lastname);
  if (address.company !== null) {
    await billingField(page, 'company').fill(address.company);
  }
  await billingField(page, 'email').fill(address.email);
  await billingField(page, 'street1').fill(address.street);
  await billingField(page, 'city').fill(address.city);
  await billingField(page, 'postcode').fill(address.postcode);
  await billingField(page, 'telephone').fill(address.telephone);
  await page.locator('#billing-buttons-container button').click();
};

const chooseFlatRateShipping = async (page: Page): Promise<void> => {
  await page.locator('#s_method_flatrate_flatrate').check();
  await page.locator('#shipping-method-buttons-container button').click();
};

const choosePaymentMethod = async (page: Page, methodCode: string): Promise<void> => {
  await page.locator(`#p_method_${methodCode}`).check({ timeout: 15_000 });
  await page.locator('#payment-buttons-container button').click();
};

const goToPaymentStep = async (page: Page, address: Address): Promise<void> => {
  await continueAsGuest(page);
  await fillBillingAddress(page, address);
  await chooseFlatRateShipping(page);
  await page.locator('#checkout-step-payment').waitFor({ state: 'visible' });
};

export const getOfferedPaymentMethods = async (page: Page, address: Address): Promise<readonly string[]> => {
  await goToPaymentStep(page, address);

  return page.locator('#checkout-step-payment input[name="payment[method]"]').evaluateAll((inputs) =>
    inputs.map((input) => (input as HTMLInputElement).value),
  );
};

export const placeGuestOrder = async (page: Page, address: Address, methodCode: string): Promise<void> => {
  await goToPaymentStep(page, address);
  await choosePaymentMethod(page, methodCode);
  await page.locator('#review-buttons-container button').click();
};
