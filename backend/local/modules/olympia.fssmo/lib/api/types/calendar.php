<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Calendar extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Calendar',
			'fields' => function ()
			{
				return [
					'items' => Type::listOf(Types::getInstance()->get('Objects')),
					'pagination' => Types::getInstance()->get('Pagination'),
				];
			}
		];

		parent::__construct($config);
	}
}