<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Ratings extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Ratings',
			'fields' => function ()
			{
				return [
					'items' => Type::listOf(Types::getInstance()->get('Rating')),
					'pagination' => Types::getInstance()->get('Pagination'),
				];
			}
		];

		parent::__construct($config);
	}
}