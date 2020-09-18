<?php

namespace Olympia\Fssmo\Api;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Query extends ObjectType
{
	public function __construct()
	{
		$config = [
			'name' => 'Query',
			'fields' => function()
			{
				return [
					'page' => [
						'type' => Types::getInstance()->get('Page'),
						'args' => [
							'url' => Type::nonNull(Type::string()),
							'area' => Types::getInstance()->get('Objects'),
						],
					],
					'currentUser' => [
						'type' => Types::getInstance()->get('User')
					],
					'currentUserResults' => [
						'type' => Type::listOf(Types::getInstance()->get('UserResult'))
					],
					'currentUserCompetitions' => [
						'type' => Type::listOf(Types::getInstance()->get('Objects'))
					],
					'currentUserRating' => [
						'type' => Type::listOf(Types::getInstance()->get('Objects'))
					],
					'externalAuth' => [
						'type' => Type::listOf(Types::getInstance()->get('ExternalAuth')),
						'args' => [
							'back_url' => Type::string()
						],
					],
					'competitions' => [
						'type' => Type::listOf(Types::getInstance()->get('Competition')),
						'args' => [
							'page' => Type::int(),
							'limit' => Type::int(),
							'filter' => Types::getInstance()->get('Objects'),
						]
					],
					'competitionsResults' => [
						'type' => Type::listOf(Types::getInstance()->get('CompetitionResult')),
						'args' => [
							'page' => Type::int(),
							'limit' => Type::int(),
						]
					],
					'competitionsFilter' => [
						'type' => Types::getInstance()->get('Objects'),
						'args' => [
							'filter' => Type::nonNull(Type::string()),
						],
					],
					'competitionsResultsFilter' => [
						'type' => Types::getInstance()->get('Objects'),
						'args' => [
							'filter' => Type::nonNull(Type::string()),
						],
					],
					'bestResults' => [
						'type' => Type::listOf(Types::getInstance()->get('Objects')),
						'args' => [
							'name' => Type::nonNull(Type::string()),
						],
					],
					'disciplines' => [
						'type' => Type::listOf(Types::getInstance()->get('Discipline')),
					],
					'siteDisciplines' => [
						'type' => Type::listOf(Types::getInstance()->get('Objects')),
					],
					'clubs' => [
						'type' => Type::listOf(Types::getInstance()->get('Club')),
					],
					'digits' => [
						'type' => Type::listOf(Types::getInstance()->get('Digit')),
					],
					'competition' => [
						'type' => Type::nonNull(Types::getInstance()->get('Competition')),
						'args' => [
							'id' => Type::nonNull(Type::string()),
						]
					],
					'participants' => [
						'type' => Type::listOf(Types::getInstance()->get('Participant')),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'competitionGroups' => [
						'type' => Type::listOf(Types::getInstance()->get('Group')),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'competitionShedule' => [
						'type' => Types::getInstance()->get('Objects'),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'competitionResults' => [
						'type' => Type::listOf(Types::getInstance()->get('Result')),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'competitionWinners' => [
						'type' => Types::getInstance()->get('Objects'),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'competitionCommandsResult' => [
						'type' => Type::listOf(Types::getInstance()->get('CommandResult')),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'photos' => [
						'type' => Type::listOf(Types::getInstance()->get('Photo')),
						'args' => [
							'competition' => Type::nonNull(Type::string()),
						]
					],
					'photoAlbums' => [
						'type' => Type::listOf(Types::getInstance()->get('PhotoAlbum')),
						'args' => [
							'page' => Type::int(),
							'limit' => Type::int(),
							'filter' => Types::getInstance()->get('Objects'),
						]
					],
					'ratings' => [
						'type' => Types::getInstance()->get('Ratings'),
						'args' => [
							'page' => Type::int(),
							'limit' => Type::int(),
							'filter' => Types::getInstance()->get('Objects'),
							'sort' => Types::getInstance()->get('Objects'),
						]
					],
					'ratingsTypes' => [
						'type' => Type::listOf(Types::getInstance()->get('ResultType')),
						'args' => []
					],
					'ratingsSuperfinal' => [
						'type' => Types::getInstance()->get('Ratings'),
						'args' => [
							'page' => Type::int(),
							'limit' => Type::int(),
						]
					],
					'news' => [
						'type' => Type::listOf(Types::getInstance()->get('News')),
						'args' => [
							'pagination' => Types::getInstance()->get('Objects'),
							'discipline' => Type::int(),
						]
					],
					'newsDetail' => [
						'type' => Type::nonNull(Types::getInstance()->get('NewsDetail')),
						'args' => [
							'id' => Type::nonNull(Type::string()),
						]
					],
					'winners' => [
						'type' => Type::listOf(Types::getInstance()->get('Winner')),
						'args' => []
					],
					'slider' => [
						'type' => Type::listOf(Types::getInstance()->get('Slider')),
						'args' => []
					],
					'sponsors' => [
						'type' => Type::listOf(Types::getInstance()->get('Sponsor')),
						'args' => []
					],
					'judges' => [
						'type' => Type::listOf(Types::getInstance()->get('Judge')),
						'args' => []
					],
					'calendarForm' => [
						'type' => Types::getInstance()->get('Objects'),
						'args' => [
							'id' => Type::string(),
						]
					],
					'calendarList' => [
						'type' => Types::getInstance()->get('Calendar'),
						'args' => [
							'page' => Type::int(),
							'limit' => Type::int(),
							'filter' => Types::getInstance()->get('Objects'),
						]
					],
					'locations' => [
						'type' => Type::listOf(Types::getInstance()->get('Objects')),
						'args' => [
							'parent' => Type::int(),
							'type' => Type::string(),
							'query' => Type::string(),
						]
					],
					'enterInClub' => [
						'type' => Types::getInstance()->get('Objects'),
						'args' => []
					],
				];
			},
			'resolveField' => function($value, $args, $context, $info)
			{
				$class = 'Olympia\\Fssmo\\Api\\Queries\\'.ucfirst($info->fieldName);

				if (class_exists($class) && method_exists($class, 'resolve'))
					return $class::resolve($value, $args, $context, $info);

				throw new Exception('can`t resolve query method `'.$info->fieldName.'`');
			}
		];

		parent::__construct($config);
	}
}