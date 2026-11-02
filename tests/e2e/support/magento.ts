import { execFileSync } from 'node:child_process';

const MAGENTO_ROOT = process.env.MAGENTO_ROOT ?? '/var/www/html/pub';

const runInMagento = (body: string, incrementId: string): string =>
  execFileSync(
    'php',
    [
      '-r',
      `ob_start(); require 'app/Mage.php'; Mage::app('admin'); $order = Mage::getModel('sales/order')->loadByIncrementId($argv[1]); ${body}`,
      incrementId,
    ],
    { cwd: MAGENTO_ROOT, encoding: 'utf8' },
  ).trim();

const SHIPMENT_ATTEMPTS = 3;

const isConcurrentWriteConflict = (error: unknown): boolean =>
  error instanceof Error && /General error: 1020|Deadlock found/.test(error.message);

const createShipment = (incrementId: string): void => {
  runInMagento(
    `$shipment = $order->prepareShipment(); $shipment->register(); $order->setIsInProcess(true);
     Mage::getModel('core/resource_transaction')->addObject($shipment)->addObject($order)->save(); ob_end_clean();`,
    incrementId,
  );
};

/**
 * Parallel tests can save two shipments at the same moment. MariaDB then rejects one of them while
 * reserving the shipment number (core eav_entity_store), so retry like an admin would.
 */
export const shipOrder = (incrementId: string, attempt = 1): void => {
  try {
    createShipment(incrementId);
  } catch (error) {
    if (attempt >= SHIPMENT_ATTEMPTS || !isConcurrentWriteConflict(error)) {
      throw error;
    }
    shipOrder(incrementId, attempt + 1);
  }
};

export const cancelOrder = (incrementId: string): void => {
  runInMagento(`$order->cancel()->save(); ob_end_clean();`, incrementId);
};

export const getMolliePaymentStatus = (incrementId: string): string =>
  runInMagento(
    `$helper = Mage::helper('mpm'); $api = $helper->getMollieAPI($helper->getApiKey($order->getStoreId()));
     $status = $api->payments->get($order->getMollieTransactionId())->status; ob_end_clean(); echo $status;`,
    incrementId,
  );
