<?php

namespace Olympia\Fssmo\Sale\Payments;

use Bitrix\Sale\Internals\BusinessValueTable;

abstract class Base
{
	private $_props = [];
	protected $_internalId = false;

	public function getProp ($name)
	{
		if (!count($this->_props) && $this->_internalId)
		{
			$list = BusinessValueTable::getList([
				'select' => ['*'],
				'filter' => ['=CONSUMER_KEY' => 'PAYSYSTEM_'.$this->_internalId]
			]);

			foreach ($list as $item)
				$this->_props[$item['CODE_KEY']] = $item['PROVIDER_VALUE'];
		}

		return isset($this->_props[$name]) ? $this->_props[$name] : '';
	}

	abstract public function createOrder ($paymentId, $paymentData = []);
	abstract public function checkOrder ($paymentId);
	public function setDocuments ($paymentId) {}

	public function setInternalId ($internalId)
	{
		$this->_internalId = $internalId;
	}
}