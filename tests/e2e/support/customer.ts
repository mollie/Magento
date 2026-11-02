export type Address = {
  readonly firstname: string;
  readonly lastname: string;
  readonly email: string;
  readonly street: string;
  readonly city: string;
  readonly postcode: string;
  readonly countryId: string;
  readonly telephone: string;
};

export const createDutchCustomer = (): Address => ({
  firstname: 'Test',
  lastname: 'Mollie',
  email: `e2e+${Date.now()}@example.com`,
  street: 'Keizersgracht 126',
  city: 'Amsterdam',
  postcode: '1015 CW',
  countryId: 'NL',
  telephone: '0201234567',
});
