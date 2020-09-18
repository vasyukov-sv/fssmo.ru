<?php

namespace Olympia\Fssmo\Db\External;

use Bitrix\Main\Orm\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Olympia\Fssmo\Db\External;

class MembershipTable extends External
{
	public $ApplicationId;
	public $UserId;
	public $Password;
	public $PasswordFormat;
	public $PasswordSalt;
	public $MobilePIN;
	public $Email;
	public $LoweredEmail;
	public $PasswordQuestion;
	public $PasswordAnswer;
	public $IsApproved;
	public $IsLockedOut;
	public $CreateDate;
	public $LastLoginDate;
	public $LastPasswordChangedDate;
	public $LastLockoutDate;
	public $FailedPasswordAttemptCount;
	public $FailedPasswordAttemptWindowStart;
	public $FailedPasswordAnswerAttemptCount;
	public $FailedPasswordAnswerAttemptWindowStart;
	public $Comment;

	/** @var UserProfilesTable */
	public $UserProfiles;

	public static function getTableName()
	{
		return 'aspnet_Membership';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('ApplicationId', [
				'required' => true
			]),
			new Fields\StringField('UserId', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Fields\StringField('Password', [
				'required' => true
			]),
			new Fields\IntegerField('PasswordFormat', [
				'PasswordFormat' => 0
			]),
			new Fields\StringField('PasswordSalt', [
				'default_value' => ''
			]),
			new Fields\StringField('MobilePIN', [
				'default_value' => null
			]),
			new Fields\StringField('Email', [
				'default_value' => null
			]),
			new Fields\StringField('LoweredEmail', [
				'default_value' => null
			]),
			new Fields\StringField('PasswordQuestion', [
				'default_value' => null
			]),
			new Fields\StringField('PasswordAnswer', [
				'default_value' => null
			]),
			new Fields\BooleanField('IsApproved', [
				'values' => [0, 1],
				'default_value' => 1
			]),
			new Fields\BooleanField('IsLockedOut', [
				'values' => [0, 1],
				'default_value' => 0
			]),
			new Fields\DatetimeField('CreateDate', [
				'default_value' => new DateTime()
			]),
			new Fields\DatetimeField('LastLoginDate', [
				'default_value' => DateTime::createFromTimestamp(0)
			]),
			new Fields\DatetimeField('LastPasswordChangedDate', [
				'default_value' => DateTime::createFromTimestamp(0)
			]),
			new Fields\DatetimeField('LastLockoutDate', [
				'default_value' => DateTime::createFromTimestamp(0)
			]),
			new Fields\IntegerField('FailedPasswordAttemptCount', [
				'default_value' => 0
			]),
			new Fields\DatetimeField('FailedPasswordAttemptWindowStart', [
				'default_value' => DateTime::createFromTimestamp(0)
			]),
			new Fields\IntegerField('FailedPasswordAnswerAttemptCount', [
				'default_value' => 0
			]),
			new Fields\DatetimeField('FailedPasswordAnswerAttemptWindowStart', [
				'default_value' => DateTime::createFromTimestamp(0)
			]),
			new Fields\TextField('Comment', [
				'default_value' => null
			]),

			(new Fields\Relations\Reference(
				'UserProfiles',
				UserProfilesTable::class,
				Join::on('this.UserId', 'ref.UserId')
			))->configureJoinType('left'),
		];
	}
}