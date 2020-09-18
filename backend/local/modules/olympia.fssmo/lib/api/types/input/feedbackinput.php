<?php

namespace Olympia\Fssmo\Api\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class FeedbackInput extends InputObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'FeedbackInput',
			'fields' => function ()
			{
				return [
					'name' => [
						'type' => Type::nonNull(Type::string())
					],
					'email' => [
						'type' => Type::nonNull(Type::string())
					],
					'text' => [
						'type' => Type::nonNull(Type::string())
					],
				];
			}
		];

		parent::__construct($config);
	}
}