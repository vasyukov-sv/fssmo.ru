<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Judge extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Judge',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'title' => Type::string(),
					'text' => Type::string(),
					'image' => Type::string(),
					'position' => Type::string(),
				];
			}
		];

		parent::__construct($config);
	}
}