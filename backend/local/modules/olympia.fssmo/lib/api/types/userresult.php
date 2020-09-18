<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class UserResult extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'UserResult',
			'fields' => function ()
			{
				return [
					'competition' => Type::string(),
					'discipline' => Type::string(),
					'date' => Type::string(),
					'summ' => Type::int(),
					'or' => Type::float(),
					'stands' => Types::getInstance()->get('Objects'),
				];
			}
		];

		parent::__construct($config);
	}
}