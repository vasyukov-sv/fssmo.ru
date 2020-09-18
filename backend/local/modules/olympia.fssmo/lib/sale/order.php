<?php

namespace Olympia\Fssmo\Sale;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale;
use Bitrix\Sale\Internals\UserBudgetPool;
use CCurrencyLang;
use CSaleUserAccount;
use CSaleUserTransact;
use Olympia\Fssmo\Exception;

/**
 * @method static Order create($siteId, $userId = null, $currency = null)
 */
class Order extends Sale\Order
{
	public static function createFromBasket (int $userId, Sale\BasketBase $basket)
	{
		if (!$basket->count())
			throw new Exception('basket is empty');

		$context = Application::getInstance()->getContext();
		$order = self::create($context->getSite(), $userId);

		$order->setBasket($basket);
		$order->setPersonTypeId(1);

		$order->addBasketToShipment(
			$order->getShipmentCollection()->getSystemShipment()
		);

		$order->getBasket()->refreshData();
		$order->refreshData();

		return $order;
	}

	public function addBasketToShipment (Sale\Shipment $shipment)
	{
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		if (!$shipment->isSystem())
			$shipment->setField('CURRENCY', $this->getCurrency());

		/** @var Sale\BasketItem $item */
		foreach ($this->getBasket() as $item)
		{
			/** @var $shipmentItem Sale\ShipmentItem */
			$shipmentItem = $shipmentItemCollection->createItem($item);

			if (!$shipment->isSystem())
				$shipmentItem->setQuantity($item->getQuantity());
			else
				$shipmentItem->setFieldNoDemand('QUANTITY', $item->getQuantity());
		}
	}

	public function getShipmentByDeliveryId (int $deliveryId)
	{
		$delivery = Sale\Delivery\Services\Manager::getById($deliveryId);

		if (!$delivery)
			throw new \Exception('unknown delivery');

		$shipmentCollection = $this->getShipmentCollection();

		$shipment = $shipmentCollection->createItem(
			Sale\Delivery\Services\Manager::createObject($delivery)
		);

		$shipment->setField('DELIVERY_NAME', $delivery['NAME']);

		return $shipment;
	}

	public function addPaymentById (int $paymentId, bool $addFromBalance = false)
	{
		$paymentObject = Sale\PaySystem\Manager::getObjectById($paymentId);

		if ($paymentObject->getField('ID') == Sale\PaySystem\Manager::getInnerPaySystemId())
		{
			$userBudget = UserBudgetPool::getUserBudgetByOrder($this);

			if (!$userBudget || $userBudget < $this->getPrice())
				throw new Exception('Не хватает средств на внутреннем счете');
		}

		$paymentCollection = $this->getPaymentCollection();

		$price = $this->getPrice();

		if ($addFromBalance === true)
		{
			$userBudget = UserBudgetPool::getUserBudgetByOrder($this);

			if (!$userBudget)
				throw new Exception('Не хватает средств на внутреннем счете');

			if ($userBudget >= $this->getPrice())
			{
				$paymentObject = Sale\PaySystem\Manager::getObjectById(
					Sale\PaySystem\Manager::getInnerPaySystemId()
				);
			}
			else
			{
				$internalPaySystemObject = Sale\PaySystem\Manager::getObjectById(Sale\PaySystem\Manager::getInnerPaySystemId());

				/** @var $internalPayment Sale\Payment */
				$internalPayment = $paymentCollection->createItem($internalPaySystemObject);

				$internalPayment->setField("SUM", $userBudget);
				$internalPayment->setField("CURRENCY", $this->getCurrency());

				$price -= $userBudget;
			}
		}

		/** @var $payment Sale\Payment */
		$payment = $paymentCollection->createItem($paymentObject);

		$payment->setField('SUM', $price);
		$payment->setField('CURRENCY', $this->getCurrency());
	}

	public function addPropertyByCode (string $code, $value)
	{
		$properties = $this->getPropertyCollection();

		$property = Sale\Internals\OrderPropsTable::query()
			->setSelect(['ID', 'CODE', 'NAME'])
			->setFilter(['=CODE' => trim($code)])
			->exec()->fetch();

		if ($property)
		{
			/* @var $prop Sale\PropertyValue */
			$prop = $properties->getItemByOrderPropertyId($property['ID']);

			if (!$prop)
				$prop = $properties->createItem($property);

			$prop->setValue($value);
		}
	}

	protected function onAfterSave()
	{
		if ($this->getId() > 0 && $this->isNew() && !$this->isPaid())
		{
			$paymentsId = $this->getPaySystemIdList();

			// При оплате с пользовательского счета ставим флаг оплаты
			if (count($paymentsId) === 1 && $paymentsId[0] == Sale\PaySystem\Manager::getInnerPaySystemId())
			{
				$userBudget = UserBudgetPool::getUserBudgetByOrder($this);

				if ($userBudget >= $this->getPrice())
				{
					$paymentCollection = $this->getPaymentCollection();

					/** @var Sale\Payment $payment */
					foreach ($paymentCollection as $payment)
					{
						$res = $payment->setPaid('Y');

						if (!$res->isSuccess())
							throw new Exception('Ошибка обновления заказа: '.implode(', ', $res->getErrorMessages()));
					}

					$res = $this->setField('STATUS_ID', 'F');

					if (!$res->isSuccess())
						throw new Exception('Ошибка обновления заказа: '.implode(', ', $res->getErrorMessages()));

					$res = $this->save();

					if (!$res->isSuccess())
						throw new Exception('Ошибка обновления заказа: '.implode(', ', $res->getErrorMessages()));
				}
			}
		}

		return parent::onAfterSave();
	}

	public function cancel ()
	{
		if (!$this->isCanceled() && !in_array($this->getField('STATUS_ID'), ['R', 'M']))
		{
			/** @var Sale\PaymentCollection $payments */
			$payments = $this->getPaymentCollection();

			/** @var $payment Sale\Payment */
			foreach ($payments as $payment)
				$payment->setPaid('N');

			/** @var Sale\ShipmentCollection $shipments */
			$shipments = $this->getShipmentCollection();

			/** @var $shipment Sale\Shipment */
			foreach ($shipments as $shipment)
			{
				if ($shipment->isSystem() || !$shipment->isShipped())
					continue;

				$shipment->setField('DEDUCTED', 'N');
				$shipment->setField('STATUS_ID', 'DN');
				$shipment->save();
			}

			$this->save();
			$this->setField('STATUS_ID', 'R');

			$r = $this->setField('CANCELED', 'Y');

			if (!$r->isSuccess())
				throw new Exception('Ошибка при отмене заказа: '.implode(', ', $r->getErrorMessages()));

			$r = $this->save();

			if (!$r->isSuccess())
				throw new Exception('Ошибка при отмене заказа: '.implode(', ', $r->getErrorMessages()));
		}

		return true;
	}

	public function refund (int $basketId, int $percent)
	{
		$basketId = (int) $basketId;

		if ($basketId <= 0)
			throw new Exception('Не указан номер элемента корзины');

		$basketItem = $this->getBasket()->getItemById($basketId);

		if (!$basketItem)
			throw new Exception('Товар не найден');

		if ($this->getField('STATUS_ID') == 'M')
			throw new Exception('Заказ уже был ранее обработан');

		if (!$this->isPaid())
			throw new Exception('Заказ еще не оплачен');

		if ($this->isCanceled())
			throw new Exception('Заказ отменен');

		/** @var Sale\PaymentCollection $paymentsCollection */
		$paymentsCollection = $this->getPaymentCollection();

		foreach ($paymentsCollection as $pay)
		{
			/** @var Sale\Payment $pay */

			if ($pay->isReturn())
				throw new Exception('Платеж уже был возвращен ранее');

			if (!$pay->isPaid())
				throw new Exception('Платеж еще не оплачен');

			$pay->setPaid('N');

			$pay->setFields([
				'IS_RETURN' => 'P',
				'PAY_RETURN_DATE' => new DateTime(),
			]);
		}

		/** @var Sale\ShipmentCollection $shipments */
		$shipments = $this->getShipmentCollection();

		/** @var $shipment Sale\Shipment */
		foreach ($shipments as $shipment)
		{
			if ($shipment->isSystem() || !$shipment->isShipped())
				continue;

			$shipment->setField('DEDUCTED', 'N');
			$shipment->setField('STATUS_ID', 'DN');
			$shipment->save();
		}

		$this->setField('STATUS_ID', 'M');
		$this->setField('CANCELED', 'Y');

		$r = $this->save();

		if (!$r->isSuccess())
			throw new Exception(implode(', ', $r->getErrorMessages()));

		$percent = (int) $percent;
		$percent = max(1, min(100, $percent)) / 100;

		$price = floor($basketItem->getField('PRICE') * $percent);

		CSaleUserAccount::UpdateAccount(
			$this->getUserId(),
			$price,
			$this->getCurrency(),
			UserBudgetPool::BUDGET_TYPE_OUT_CHARGE_OFF,
			$this->getId(), 'Отмена заказа и возврат на личный счет', $paymentsCollection[0]->getId()
		);

		$res = CSaleUserTransact::GetList(['ID' => 'DESC'], ['USER_ID' => $this->getUserId()], false, ['nTopCount' => 1], ['ID'])->Fetch();

		$transactionId = $res ? (int) $res['ID'] : 0;

		foreach ($paymentsCollection as $pay)
		{
			/** @var Sale\Payment $pay */

			$pay->setFields([
				'PAY_RETURN_COMMENT' => 'Возврат на личный счет: '.CCurrencyLang::CurrencyFormat($price, $pay->getField('CURRENCY')),
				'PAY_RETURN_NUM' => $transactionId
			]);

			$pay->save();
		}

		return $r;
	}
}