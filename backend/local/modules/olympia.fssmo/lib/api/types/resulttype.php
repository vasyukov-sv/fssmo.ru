<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class ResultType extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'ResultType',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'code' => Type::string(),
					'title' => Type::string(),
					'count' => Type::int(),
				];
			}
		];

		parent::__construct($config);
	}
}