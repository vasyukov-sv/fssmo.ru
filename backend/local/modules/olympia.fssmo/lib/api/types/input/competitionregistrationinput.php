<?php

namespace Olympia\Fssmo\Api\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class CompetitionRegistrationInput extends InputObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'CompetitionRegistrationInput',
			'fields' => function ()
			{
				return [
					'name' => [
						'type' => Type::nonNull(Type::string())
					],
					'last_name' => [
						'type' => Type::nonNull(Type::string())
					],
					'middle_name' => [
						'type' => Type::nonNull(Type::string())
					],
					'email' => [
						'type' => Type::nonNull(Type::string())
					],
					'phone' => [
						'type' => Type::nonNull(Type::string())
					],
					'city' => [
						'type' => Type::nonNull(Type::string())
					],
					'digit' => [
						'type' => Type::nonNull(Type::int())
					],
					'club' => [
						'type' => Type::nonNull(Type::int())
					],
				];
			}
		];

		parent::__construct($config);
	}
}