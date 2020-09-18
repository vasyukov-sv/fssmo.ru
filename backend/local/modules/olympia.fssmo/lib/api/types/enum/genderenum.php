<?php

namespace Olympia\Fssmo\Api\Types\Enum;

use GraphQL\Type\Definition\EnumType;

class GenderEnum extends EnumType
{
	public function __construct()
	{
		$config = [
			'name' => 'GenderEnum',
			'values' => [
				'',
				'M',
				'F'
			]
		];

		parent::__construct($config);
	}
}