import { execFileSync } from 'node:child_process';

export type PlacedOrder = {
  readonly incrementId: string;
  readonly state: string;
  readonly grandTotal: string;
  readonly totalPaid: string | null;
  readonly invoiceCount: number;
};

const queryDatabase = (sql: string): string =>
  execFileSync('mysql', ['--batch', '--skip-column-names', '-e', sql], { encoding: 'utf8' }).trim();

const parseOrder = (row: string): PlacedOrder => {
  const [incrementId, state, grandTotal, totalPaid, invoiceCount] = row.split('\t');

  return {
    incrementId,
    state,
    grandTotal,
    totalPaid: totalPaid === 'NULL' ? null : totalPaid,
    invoiceCount: Number(invoiceCount),
  };
};

export const findLatestOrderByEmail = (email: string): PlacedOrder | null => {
  const row = queryDatabase(`
    SELECT o.increment_id, o.state, o.grand_total, o.total_paid,
           (SELECT COUNT(*) FROM sales_flat_invoice i WHERE i.order_id = o.entity_id)
    FROM sales_flat_order o
    WHERE o.customer_email = '${email.replace(/'/g, "''")}'
    ORDER BY o.entity_id DESC
    LIMIT 1
  `);

  return row === '' ? null : parseOrder(row);
};
