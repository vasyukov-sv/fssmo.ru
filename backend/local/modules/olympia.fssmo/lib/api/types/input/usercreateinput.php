<?php

namespace Olympia\Fssmo\Api\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class UserCreateInput extends InputObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'UserCreateInput',
			'fields' => function ()
			{
				return [
					'email' => [
						'type' => Type::nonNull(Type::string())
					],
					'password' => [
						'type' => Type::nonNull(Type::string())
					],
					'password_confirm' => [
						'type' => Type::nonNull(Type::string())
					],
					'name' => [
						'type' => Type::string()
					],
					'last_name' => [
						'type' => Type::string()
					],
					'phone' => [
						'type' => Type::string()
					],
				];
			}
		];

		parent::__construct($config);
	}
}