<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class CommandResult extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'CommandResult',
			'fields' => function ()
			{
				return [
					'command' => Type::string(),
					'summ' => Type::int(),
					'participants' => Type::listOf(Types::getInstance()->get('Result')),
				];
			}
		];

		parent::__construct($config);
	}
}