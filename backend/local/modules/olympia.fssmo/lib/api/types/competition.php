<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class Competition extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Competition',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'code' => Type::string(),
					'url' => Type::string(),
					'title' => Type::string(),
					'date_from' => Type::string(),
					'date_to' => Type::string(),
					'discipline' => Type::string(),
					'location' => Type::string(),
					'image' => Type::string(),
					'stands' => Type::int(),
					'targets' => Type::int(),
					'shooters' => Type::int(),
					'max_shooters' => Type::int(),
					'detail_text' => Type::string(),
					'registration' => Type::boolean(),
					'tabs' => Types::getInstance()->get('Objects'),
					'protocols' => Type::listOf(Type::string()),
					'price' => Types::getInstance()->get('CompetitionPrice'),
				];
			}
		];

		parent::__construct($config);
	}
}