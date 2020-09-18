<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class GroupParticipant extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'GroupParticipant',
			'fields' => function ()
			{
				return [
					'number' => Type::int(),
					'name' => Type::string()
				];
			}
		];

		parent::__construct($config);
	}
}