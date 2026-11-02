import { queryDatabase, quote } from './database';

export type PlacedOrder = {
  readonly incrementId: string;
  readonly state: string;
  readonly grandTotal: string;
  readonly totalPaid: string | null;
  readonly invoiceCount: number;
  readonly mollieTransactionId: string;
  readonly paymentFee: string | null;
};

const parseOrder = (row: string): PlacedOrder => {
  const [incrementId, state, grandTotal, totalPaid, invoiceCount, mollieTransactionId, paymentFee] = row.split('\t');

  return {
    incrementId,
    state,
    grandTotal,
    totalPaid: totalPaid === 'NULL' ? null : totalPaid,
    invoiceCount: Number(invoiceCount),
    mollieTransactionId,
    paymentFee: paymentFee === 'NULL' ? null : paymentFee,
  };
};

export const findLatestOrderByEmail = (email: string): PlacedOrder | null => {
  const row = queryDatabase(`
    SELECT o.increment_id, o.state, o.grand_total, o.total_paid,
           (SELECT COUNT(*) FROM sales_flat_invoice i WHERE i.order_id = o.entity_id),
           o.mollie_transaction_id, o.mollie_mpm_payment_fee
    FROM sales_flat_order o
    WHERE o.customer_email = ${quote(email)}
    ORDER BY o.entity_id DESC
    LIMIT 1
  `);

  return row === '' ? null : parseOrder(row);
};
