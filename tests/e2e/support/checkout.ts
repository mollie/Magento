import { Page } from '@playwright/test';
import { Address } from './customer';

export const SIMPLE_PRODUCT_URL = '/retro-chic-eyeglasses.html';

const billingField = (page: Page, name: string) => page.locator(`#billing\\:${name}`);

export const addProductToCart = async (page: Page, productUrl: string): Promise<void> => {
  await page.goto(productUrl);
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
  await billingField(page, 'firstname').fill(address.firstname);
  await billingField(page, 'lastname').fill(address.lastname);
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
  await page.locator(`#p_method_${methodCode}`).check();
  await page.locator('#payment-buttons-container button').click();
};

export const placeGuestOrder = async (page: Page, address: Address, methodCode: string): Promise<void> => {
  await continueAsGuest(page);
  await fillBillingAddress(page, address);
  await chooseFlatRateShipping(page);
  await choosePaymentMethod(page, methodCode);
  await page.locator('#review-buttons-container button').click();
};
