<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Winner extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Winner',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'date' => Type::string(),
					'name' => Type::string(),
					'last_name' => Type::string(),
					'image' => Type::string(),
					'discipline' => Type::string(),
					'club' => Type::string(),
					'digit' => Type::string(),
					'description' => Type::string(),
					'result' => Type::int(),
					'result_max' => Type::int(),
				];
			}
		];

		parent::__construct($config);
	}
}