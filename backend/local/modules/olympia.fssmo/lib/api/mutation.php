<?php

namespace Olympia\Fssmo\Api;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Mutation extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Mutation',
			'fields' => function()
			{
				return [
					'userLogin' => [
						'type' => Type::nonNull(Types::getInstance()->get('User')),
						'args' => [
							'login' => Type::nonNull(Type::string()),
							'password' => Type::nonNull(Type::string()),
							'remember' => Type::boolean(),
						],
					],
					'userCreate' => [
						'type' => Type::nonNull(Types::getInstance()->get('UserCreateOutput')),
						'args' => [
							'user' => Type::nonNull(Types::getInstance()->get('UserCreateInput')),
						],
					],
					'userRemember' => [
						'type' => Type::nonNull(Type::string()),
						'args' => [
							'login' => Type::nonNull(Type::string())
						],
					],
					'userLogout' => [
						'type' => Type::nonNull(Type::boolean()),
					],
					'userUpdate' => [
						'type' => Type::nonNull(Types::getInstance()->get('User')),
						'args' => [
							'user' => Type::nonNull(Types::getInstance()->get('UserUpdateInput')),
						],
					],
					'userUpdatePassword' => [
						'type' => Type::nonNull(Type::boolean()),
						'args' => [
							'data' => Type::nonNull(Types::getInstance()->get('UserUpdatePasswordInput')),
						],
					],
					'feedbackForm' => [
						'type' => Type::nonNull(Type::boolean()),
						'args' => [
							'data' => Type::nonNull(Types::getInstance()->get('FeedbackInput')),
						],
					],
					'enterForm' => [
						'type' => Type::nonNull(Types::getInstance()->get('Objects')),
						'args' => [
							'data' => Type::nonNull(Types::getInstance()->get('Objects')),
							'payment' => Type::nonNull(Type::string()),
						],
					],
					'renewMembership' => [
						'type' => Type::nonNull(Types::getInstance()->get('Objects')),
						'args' => [
							'payment' => Type::nonNull(Type::string()),
						],
					],
					'calendarForm' => [
						'type' => Type::nonNull(Type::boolean()),
						'args' => [
							'id' => Type::string(),
							'data' => Type::nonNull(Types::getInstance()->get('Objects')),
						],
					],
					'competitionRegistration' => [
						'type' => Type::nonNull(Types::getInstance()->get('Objects')),
						'args' => [
							'competition' => Type::nonNull(Type::int()),
							'payment' => Type::string(),
							'data' => Type::nonNull(Types::getInstance()->get('CompetitionRegistrationInput')),
						],
					],
					'competitionPayment' => [
						'type' => Type::nonNull(Types::getInstance()->get('Objects')),
						'args' => [
							'competition' => Type::nonNull(Type::int()),
						],
					],
					'cancelCompetitionRegistration' => [
						'type' => Type::nonNull(Type::boolean()),
						'args' => [
							'id' => Type::nonNull(Type::int()),
						],
					]
				];
			},
			'resolveField' => function($value, $args, $context, $info)
			{
				$class = 'Olympia\\Fssmo\\Api\\Mutations\\'.ucfirst($info->fieldName);

				if (class_exists($class) && method_exists($class, 'resolve'))
					return $class::resolve($value, $args, $context, $info);

				throw new Exception('can`t resolve mutation method `'.$info->fieldName.'`');
			}
		];

		parent::__construct($config);
	}
}