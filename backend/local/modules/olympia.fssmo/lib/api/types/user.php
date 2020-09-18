<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class User extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'User',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'name' => Type::string(),
					'last_name' => Type::string(),
					'middle_name' => Type::string(),
					'email' => Type::string(),
					'phone' => Type::string(),
					'digit' => Type::int(),
					'club' => Type::int(),
					'city' => Type::string(),
					'birthday' => Type::string(),
					'avatar' => Type::string(),
					'subscribe' => Type::boolean(),
					'agreement' => Type::boolean(),
					'admin' => Type::boolean(),
					'sex' => Types::getInstance()->get('GenderEnum'),
					'budget' => Types::getInstance()->get('Objects'),
					'in_club' => Types::getInstance()->get('Objects'),
				];
			}
		];

		parent::__construct($config);
	}
}