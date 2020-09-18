<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Digit extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Digit',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'title' => Type::string(),
				];
			}
		];

		parent::__construct($config);
	}
}