<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Participant extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Participant',
			'fields' => function ()
			{
				return [
					'name' => Type::string(),
					'city' => Type::string(),
					'club' => Type::string(),
					'digit' => Type::string(),
				];
			}
		];

		parent::__construct($config);
	}
}