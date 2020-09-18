<?php

namespace Olympia\Fssmo\Api\Types\Output;

use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;
use Olympia\Fssmo\Api\Internals\Buffer;

class UserCreateOutput extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'UserCreateOutput',
			'fields' => function ()
			{
				return [
					'status' => [
						'type' => Type::boolean()
					],
					'user' => [
						'type' => Types::getInstance()->get('User'),
						'resolve' => function (/** @noinspection PhpUnusedParameterInspection */$root, $args)
						{
							Buffer\User::add($root['user']);

							return new Deferred(function () use ($root)
							{
								return Buffer\User::get($root['user']);
							});
						}
					]
				];
			}
		];

		parent::__construct($config);
	}
}