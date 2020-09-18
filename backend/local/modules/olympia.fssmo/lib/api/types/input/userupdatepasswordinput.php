<?php

namespace Olympia\Fssmo\Api\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class UserUpdatePasswordInput extends InputObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'UserUpdatePasswordInput',
			'fields' => function ()
			{
				return [
					'login' => [
						'type' => Type::string()
					],
					'checkword' => [
						'type' => Type::string()
					],
					'password' => [
						'type' => Type::string()
					],
					'confirm' => [
						'type' => Type::string()
					],
				];
			}
		];

		parent::__construct($config);
	}
}