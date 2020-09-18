<?php

namespace Olympia\Fssmo\Api\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Olympia\Fssmo\Api\Types;

class CompetitionResult extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'CompetitionResult',
			'fields' => function ()
			{
				return [
					'id' => Type::int(),
					'title' => Type::string(),
					'url' => Type::string(),
					'location' => Type::string(),
					'discipline' => Type::string(),
					'members' => Type::int(),
					'date' => Type::string(),
					'groups' => Type::int(),
					'targets' => Type::int(),
					'winner' => Types::getInstance()->get('Objects')
				];
			}
		];

		parent::__construct($config);
	}
}