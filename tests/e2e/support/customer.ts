export type CountryCode = 'NL' | 'ES';

export type Address = {
  readonly firstname: string;
  readonly lastname: string;
  readonly email: string;
  readonly street: string;
  readonly city: string;
  readonly postcode: string;
  readonly region: string | null;
  readonly countryId: CountryCode;
  readonly telephone: string;
};

type Location = Pick<Address, 'street' | 'city' | 'postcode' | 'region' | 'telephone'>;

const LOCATIONS: Readonly<Record<CountryCode, Location>> = {
  NL: { street: 'Keizersgracht 126', city: 'Amsterdam', postcode: '1015 CW', region: null, telephone: '0201234567' },
  ES: { street: 'Calle de Alcalá 1', city: 'Madrid', postcode: '28014', region: 'Madrid', telephone: '910000000' },
};

export const createCustomer = (countryId: CountryCode): Address => ({
  firstname: 'Test',
  lastname: 'Mollie',
  email: `e2e+${countryId.toLowerCase()}${Date.now()}@example.com`,
  countryId,
  ...LOCATIONS[countryId],
});
