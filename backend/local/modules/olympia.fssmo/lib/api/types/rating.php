<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Rating extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Rating',
			'fields' => function ()
			{
				return [
					'place' => Type::int(),
					'diff' => Type::int(),
					'name' => Type::string(),
					'city' => Type::string(),
					'club' => Type::string(),
					'digit' => Type::string(),
					'targets' => Type::int(),
					'rating' => Type::float(),
					'group' => Type::string(),
					'competitions' => Type::listOf(Types::getInstance()->get('Objects')),
				];
			}
		];

		parent::__construct($config);
	}
}