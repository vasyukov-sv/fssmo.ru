<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Slider extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Slider',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'title' => Type::string(),
					'image' => Type::string(),
					'description' => Type::string(),
					'date_from' => Type::string(),
					'date_to' => Type::string(),
					'button_text' => Type::string(),
					'button_url' => Type::string(),
				];
			}
		];

		parent::__construct($config);
	}
}