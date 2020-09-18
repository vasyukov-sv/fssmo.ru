<?php

namespace Olympia\Fssmo\Api\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class UserUpdateInput extends InputObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'UserUpdateInput',
			'fields' => function ()
			{
				return [
					'email' => [
						'type' => Type::string()
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
					'city' => [
						'type' => Type::string()
					],
					'club' => [
						'type' => Type::int()
					],
					'digit' => [
						'type' => Type::int()
					],
					'birthday' => [
						'type' => Type::string()
					],
					'avatar' => [
						'type' => Type::string()
					],
					'password_old' => [
						'type' => Type::string()
					],
					'password' => [
						'type' => Type::string()
					],
					'password_confirm' => [
						'type' => Type::string()
					],
					'subscribe' => [
						'type' => Type::boolean()
					],
					'agreement' => [
						'type' => Type::boolean()
					],
				];
			}
		];

		parent::__construct($config);
	}
}