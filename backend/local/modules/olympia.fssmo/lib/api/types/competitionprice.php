<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CompetitionPrice extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'CompetitionPrice',
			'fields' => function ()
			{
				return [
					'currency' => Type::string(),
					'value' => Type::float(),
				];
			}
		];

		parent::__construct($config);
	}
}