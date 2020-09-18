<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Group extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Group',
			'fields' => function ()
			{
				return [
					'number' => Type::int(),
					'participants' => Type::listOf(Types::getInstance()->get('GroupParticipant'))
				];
			}
		];

		parent::__construct($config);
	}
}