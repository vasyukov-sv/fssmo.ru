<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Discipline extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Discipline',
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