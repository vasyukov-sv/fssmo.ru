<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Result extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Result',
			'fields' => function ()
			{
				return [
					'place' => Type::int(),
					'group' => Type::string(),
					'number' => Type::int(),
					'name' => Type::string(),
					'summ' => Type::int(),
					'country' => Type::string(),
					'city' => Type::string(),
					'club' => Type::string(),
					'digit' => Type::string(),
					'digitNew' => Type::string(),
					'stands' => Types::getInstance()->get('Objects'),
				];
			}
		];

		parent::__construct($config);
	}
}