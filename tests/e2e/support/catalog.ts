import { queryDatabase, quote } from './database';

export type Product = {
  readonly url: string;
  readonly sku: string;
};

export const SIMPLE_PRODUCT: Product = { url: '/retro-chic-eyeglasses.html', sku: 'ace002' };

const STOCK_QUANTITY = 1000;

export const restockProduct = (product: Product): void => {
  const productId = `(SELECT entity_id FROM catalog_product_entity WHERE sku = ${quote(product.sku)})`;
  queryDatabase(`
    UPDATE cataloginventory_stock_item SET qty = ${STOCK_QUANTITY}, is_in_stock = 1 WHERE product_id = ${productId};
    UPDATE cataloginventory_stock_status SET qty = ${STOCK_QUANTITY}, stock_status = 1 WHERE product_id = ${productId};
  `);
};
