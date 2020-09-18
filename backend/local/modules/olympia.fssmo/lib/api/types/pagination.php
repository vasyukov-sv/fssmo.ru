<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Pagination extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Pagination',
			'fields' => function ()
			{
				return [
					'total' => Type::int(),
					'limit' => Type::int(),
					'page' => Type::int(),
				];
			}
		];

		parent::__construct($config);
	}
}