<?php

namespace Olympia\Fssmo\Competition;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use CIBlock;
use CUser;
use Olympia\Bitrix\Helpers;
use Olympia\Fssmo\Api\Exception;
use Olympia\Fssmo\Db\External;
use Olympia\Fssmo\Model\CompetitionsPriceTable;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Main;
use Olympia\Fssmo;
use Olympia\Fssmo\Model\EnterTable;

class Order
{
	const DEFAULT_PAYMENT_ID = 2;

	public static function getOfferByRestrictions (int $competitionId, ?int $userId): ?CompetitionsPriceTable
	{
		$items = CompetitionsPriceTable::query()
			->setOrder(['PRICE.PRICE' => 'ASC'])
			->setSelect(['ID', 'NAME', 'PROPERTY.CATEGORY', 'PRICE_' => 'PRICE.*'])
			->setFilter(['=PROPERTY.CML2_LINK' => $competitionId]);

		if (!$userId)
			$items->setLimit(1);

		$items = $items->exec();

		if ($items->getSelectedRowsCount() == 0)
			return null;

		$result = null;

		if (!$userId)
			$result = $items->fetch();
		else
		{
			$categories = [];

			$enums = PropertyEnumerationTable::query()
				->setSelect(['ID', 'XML_ID'])
				->setFilter(['=PROPERTY.CODE' => 'CATEGORY', '=PROPERTY.IBLOCK_ID' => IBLOCK_COMPETITIONS_PRICE])
				->cacheJoins(true)
				->setCacheTtl(86400)
				->exec();

			foreach ($enums as $enum)
				$categories[$enum['ID']] = $enum['XML_ID'];

			$userData = UserTable::query()
				->setSelect(['ID', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDAY', 'UF_CLUB_ID', 'XML_ID'])
				->setFilter(['=ID' => $userId])
				->exec()->fetch();

			$offers = [];

			/** @var CompetitionsPriceTable $item */
			foreach ($items as $item)
			{
				if ($item->getProperty('CATEGORY') > 0)
				{
					$categoryCode = $categories[$item->getProperty('CATEGORY')] ?? 'UNKNOWN';

					switch ($categoryCode)
					{
						case 'VETERAN':
						case 'SUPER_VETERAN':
						case 'JONIOR':
							if (!$userData['PERSONAL_BIRTHDAY'])
								continue;

							$year = $userData['PERSONAL_BIRTHDAY']->format('Y');

							if ($categoryCode == 'VETERAN' && ($year < 1953 || $year > 1963))
								continue 2;
							elseif ($categoryCode == 'SUPER_VETERAN' && $year > 1953)
								continue 2;
							elseif ($categoryCode == 'JONIOR' && ($year < 1999 || $year > 2005))
								continue 2;

							break;
						case 'MALE':

							if ($userData['PERSONAL_GENDER'] != 'M')
								continue;

							break;
						case 'FEMALE':

							if ($userData['PERSONAL_GENDER'] != 'F')
								continue 2;

							break;
						case 'IN_FSSMO_CLUB':

							/** @var External\ShootersTable */
							$isInFssmo = External\ShootersTable::query()
								->setSelect(['id'])
								->setFilter(['=UserId' => $userData['XML_ID'], '=IsInFssmo' => true, '>ExpirationDate' => new DateTime()])
								->exec()->fetch();

							if (!$isInFssmo)
								continue 2;

							break;
						case 'FIRST':

							$isRegistered = External\RegistredUsersTable::query()
								->setSelect(['id'])
								->setFilter([
									'=UserId' => $userData['XML_ID'],
									'=Refused' => false,
									'=Banned' => false
								])
								->exec()->fetch();

							if ($isRegistered)
								continue 2;

							break;
						case 'FIRST_IN_TYPE':

							$siteId = Fssmo\Competition::getExternalSiteId($competitionId);

							/** @var External\CompetitionsCalendarTable $comp */
							$comp = External\CompetitionsCalendarTable::query()
								->setSelect(['id', 'SiteDiscipline'])
								->setFilter(['=id' => $siteId])
								->setCacheTtl(86400)
								->exec()->fetch();

							$isRegistered = External\RegistredUsersTable::query()
								->setSelect(['id'])
								->setFilter([
									'=UserId' => $userData['XML_ID'],
									'=Competition.SiteDiscipline' => $comp->SiteDiscipline,
									'=Refused' => false,
									'=Banned' => false
								])
								->exec()->fetch();

							if ($isRegistered)
								continue 2;

							break;
					}
				}

				$offers[] = $item;
			}

			$result = $offers[0] ?? null;
		}

		if ($result)
		{
			$price = Fssmo\Discounts::getPrice([$result['ID']]);

			if (isset($price[$result['ID']]))
			{
				$result['PRICE_PRICE'] = $price[$result['ID']]['VALUE'];
				$result['PRICE_CURRENCY'] = $price[$result['ID']]['CURRENCY'];
			}
		}

		return $result;
	}

	public static function createOrder (int $competitionId, int $userId, bool $payFromBalance = false): int
	{
		$context = Application::getInstance()->getContext();

		$offer = Fssmo\Competition\Order::getOfferByRestrictions($competitionId, $userId);

		if (!$offer)
			throw new Exception('Цена не найдена');

		$isPayed = Sale\Internals\BasketTable::query()
			->setSelect(['ID'])
			->setFilter([
				'=PRODUCT_ID' => $offer->ID,
				'=ORDER.PAYED' => 'Y',
				'=ORDER.STATUS_ID' => 'Y',
			])
			->exec()->fetch();

		if ($isPayed)
			throw new Exception('Соревнование уже оплачено');

		$basket = Sale\Basket::create($context->getSite());
		$basket->setFUserId(Sale\Fuser::getIdByUserId($userId));

		$category = 'Общая';

		if ($offer->getProperty('CATEGORY') > 0)
		{
			$_cat = PropertyEnumerationTable::query()
				->setSelect(['ID', 'VALUE'])
				->setFilter([
					'=PROPERTY.CODE' => 'CATEGORY',
					'=PROPERTY.IBLOCK_ID' => IBLOCK_COMPETITIONS_PRICE,
					'=ID' => $offer->getProperty('CATEGORY'),
				])
				->exec()->fetch();

			if ($_cat)
				$category = $_cat['VALUE'];
		}

		$res = Catalog\Product\Basket::addProductToBasket($basket, [
			'PRODUCT_ID' => $offer->ID,
			'QUANTITY' => 1,
			'PRICE' => $offer['PRICE_PRICE'],
			'BASE_PRICE' => $offer['PRICE_PRICE'],
			'CURRENCY' => $offer['PRICE_CURRENCY'],
			'PROPS' => [[
				'NAME' => 'Категория',
				'CODE' => 'CATEGORY',
				'VALUE' => $category,
			]]
		], [
			'SITE_ID' => $context->getSite(),
			'USER_ID' => $userId,
		]);

		if (!$res->isSuccess())
			throw new Exception('basket item create error: '.implode(', ', $res->getErrorMessages()));

		$order = Fssmo\Sale\Order::createFromBasket($userId, $basket);
		$order->addPaymentById(Fssmo\Competition\Order::DEFAULT_PAYMENT_ID, $payFromBalance);

		$res = $order->save();

		if (!$res->isSuccess())
			throw new Exception('order create error: '.implode(', ', $res->getErrorMessages()));

		return (int) $order->getId();
	}

	public static function getPaymentData ($orderId)
	{
		$result = [];

		if ($orderId > 0)
		{
			/** @var Sale\Order $order */
			$order = Fssmo\Sale\Order::load($orderId);

			if ($order)
			{
				/** @var Sale\PaymentCollection $paymentCollection */
				$paymentCollection = $order->getPaymentCollection();

				if ($paymentCollection)
				{
					/** @var Sale\Payment|null $paymentItem */
					$paymentItem = null;

					/** @var Sale\Payment $item */
					foreach ($paymentCollection as $item)
					{
						if (!$item->isInner() && !$item->isPaid())
						{
							$paymentItem = $item;
							break;
						}
					}

					if ($paymentItem !== null)
					{
						$service = Sale\PaySystem\Manager::getObjectById($paymentItem->getPaymentSystemId());

						if ($service)
						{
							$payResult = $service->initiatePay($paymentItem, null, Sale\PaySystem\BaseServiceHandler::STRING);

							if (!$payResult->isSuccess())
								$result['message'] = implode('<br>', $payResult->getErrorMessages());
							else
								$result['template'] = $payResult->getTemplate();
						}
					}
				}
			}
		}

		return $result;
	}

	public static function onSaleOrderPaidHandler (Event $event)
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');

		$isPaid = $order->isPaid();

		if ($isPaid)
		{
			/* @var $basketItem Sale\BasketItem */
			foreach ($order->getBasket()->getBasketItems() as $basketItem)
			{
				$element = ElementTable::query()
					->setSelect(['ID', 'CODE', 'IBLOCK_ID'])
					->setFilter(['=ID' => $basketItem->getField('PRODUCT_ID')])
					->exec()->fetch();

				if ($element['IBLOCK_ID'] == IBLOCK_COMPETITIONS_PRICE)
				{
					$profileEntity = _hl(4);

					$registrationProfile = $profileEntity::query()
						->setSelect(['*'])
						->setFilter(['=UF_BASKET_ID' => $basketItem->getId()])
						->exec()->fetch();

					if ($registrationProfile)
					{
						/** @var CompetitionsPriceTable $offer */
						$offer = CompetitionsPriceTable::query()
							->setSelect(['ID', 'PROPERTY.CML2_LINK'])
							->setFilter(['=ID' => $basketItem->getField('PRODUCT_ID')])
							->exec()->fetch();

						if ($offer)
						{
							try
							{
								Fssmo\Competition::registration(
									$offer->getProperty('CML2_LINK'),
									$order->getUserId(),
									unserialize($registrationProfile['UF_DATA'])
								);
							}
							catch (\Exception $e)
							{
								Sale\OrderHistory::addAction(
									'ORDER',
									$order->getId(),
									'ORDER_UPDATE_ERROR',
									$order->getId(),
									$order,
									array("ERROR" => 'Fssmo\Competition::registration: '.$e->getMessage())
								);
							}
						}
					}
				}
				elseif ($element['IBLOCK_ID'] == IBLOCK_SERVICES)
				{
					if ($element['CODE'] == 'FSSMO_MEMBERSHIP_FEE')
					{
						$user = UserTable::query()
							->setSelect(['ID', 'XML_ID'])
							->setFilter(['=ID' => $order->getUserId(), '!XML_ID' => false])
							->exec()->fetch();

						if ($user)
						{
							try
							{
								$shooterId = Fssmo\User::createOrGetShooter($user['XML_ID']);

								if ($shooterId)
								{
									$userObj = new CUser;
									$userObj->Update($order->getUserId(), [
										'UF_SHOOTER_ID' => $shooterId,
										'UF_CLUB_ID' => 8,
									]);

									/** @var External\ShootersTable $shooter */
									$shooter = External\ShootersTable::query()
										->setSelect(['id', 'FssmoRegDate', 'ExpirationDate'])
										->setFilter(['=id' => $shooterId])
										->exec()->fetch();

									if ($shooter)
									{
										$untilDate = (new DateTime());

										if ($shooter->ExpirationDate)
										{
											$time = strtotime((string) $shooter->ExpirationDate);

											if ($time > time())
												$untilDate = DateTime::createFromTimestamp($time);
										}

										$untilDate->add('+1 year')->setTime(0, 0, 0);

										$regDate = $shooter->FssmoRegDate ? $shooter->FssmoRegDate : (new DateTime());
										$regDate->setTime(0, 0, 0);

										External\ShootersTable::update($shooterId, [
											'ClubId' => 8,
											'IsInFssmo' => 1,
											'FssmoRegDate' => $regDate,
											'ExpirationDate' => $untilDate,
										]);
									}

									$profile = External\UserProfilesTable::query()
										->setSelect(['id'])
										->setFilter(['=UserId' => $user['XML_ID']])
										->exec()->fetch();

									if ($profile)
									{
										External\UserProfilesTable::update($shooterId, [
											'ClubId' => 8,
										]);
									}
								}
							}
							catch (\Exception $e)
							{
								Sale\OrderHistory::addAction(
									'ORDER',
									$order->getId(),
									'ORDER_UPDATE_ERROR',
									$order->getId(),
									$order,
									array("ERROR" => 'Registration in FSSMO: '.$e->getMessage())
								);
							}
						}
					}

					if ($element['CODE'] == 'FSSMO_ENTRANCE_FEE')
					{
						$enterForm = EnterTable::query()
							->setSelect(['ID'])
							->setFilter(['PROPERTY.ORDER_ID' => $order->getId()])
							->exec()->fetch();

						if ($enterForm)
						{
							$fields = Helpers::getFieldsFromIblock($enterForm['ID'], IBLOCK_FORM_ENTER);

							Main\Mail\Event::send([
								'EVENT_NAME' => 'FORM_ENTER',
								'LID' => 's1',
								'DUPLICATE' => 'N',
								'C_FIELDS' => $fields,
								'LANGUAGE_ID' => LANGUAGE_ID,
							]);
						}
					}
				}
			}
		}
	}

	public static function onBeforeSaleBasketItemEntityDeletedHandler (Event $event)
	{
		/* @var $item Sale\BasketItem */
		$item = $event->getParameter('ENTITY');

		$profileClass = _hl(4);

		$checkProfile = $profileClass::getRow([
			'select' => ['ID'],
			'filter' => ['=UF_BASKET_ID' => $item->getId()]
		]);

		if ($checkProfile)
			$profileClass::delete($checkProfile['ID']);
	}

	public static function onSaleOrderBeforeSavedHandler (Event $event)
	{
		/** @var Sale\Order $order */
		$order = $event->getParameter('ENTITY');

		$pool = Sale\Internals\UserBudgetPool::getUserBudgetPool($order->getUserId());

		foreach ($pool->get() as $key => $budgetDat)
		{
			if ($budgetDat['TYPE'] == 'EXCESS_SUM_PAID')
				$pool->delete($key);
		}
	}

	public static function onSaleOrderCanceledHandler (Event $event)
	{
		/** @var Fssmo\Sale\Order $order */
		$order = $event->getParameter('ENTITY');

		CIBlock::clearIblockTagCache(IBLOCK_COMPETITIONS);

		if (defined('SKIP_ORDER_CANCELED_BUDGET_ADD_'.$order->getId()) || in_array($order->getField('STATUS_ID'), ['R', 'M']))
			return;

		if ($order->isCanceled())
		{
			$object = Fssmo\Sale\Order::load($order->getId());
			$object->setField('STATUS_ID', 'R');
			$object->save();
		}
	}

	public static function OnSalePsServiceProcessRequestAfterPaidHandler (Event $event)
	{
		$order_id = (int) $event->getParameter('order_id');

		if ($order_id > 0)
			self::onAfterPaymentHandler($order_id);
	}

	public static function onAfterPaymentHandler ($orderId)
	{
		$order = Fssmo\Sale\Order::load((int) $orderId);

		if (!$order)
			return false;

		/** @var Sale\PaymentCollection $payments */
		$payments = $order->getPaymentCollection();

		$payed = $order->isPaid();

		if ($payed)
			return true;

		$hasInnerPayment = false;
		$hasPayedPayment = false;

		/** @var $payment Sale\Payment */
		foreach ($payments as $payment)
		{
			if ($payment->isInner() && !$payment->isPaid())
				$hasInnerPayment = true;
			elseif (!$payment->isInner() && $payment->isPaid())
				$hasPayedPayment = true;
		}

		if ($hasInnerPayment && $hasPayedPayment)
		{
			/** @var $payment Sale\Payment */
			foreach ($payments as $payment)
			{
				if ($payment->isInner())
					$payment->setPaid('Y');
			}
		}

		$res = $order->save();

		return $res->isSuccess();
	}
}