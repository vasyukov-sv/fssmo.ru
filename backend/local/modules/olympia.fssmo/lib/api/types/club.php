<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Club extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Club',
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