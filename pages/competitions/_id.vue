<template>
	<div class="page-content">
		<HeaderContent :headerClass="'page-content-header-dark event-detail-header'">
			<div class="event-header-info" :class="{'not-passed': competition['registration']}">
				<div class="event-header-info-left">
					<div class="event-header-info-item"><span class="event-icon event-icon-date"></span>{{ date }}</div>
					<div v-if="competition['location']" class="event-header-info-item">
						<span class="event-icon event-icon-map"></span><span v-html="competition['location']"></span>
					</div>
					<div v-if="competition['discipline']" class="event-header-info-item">
						<span class="event-icon event-icon-gun"></span>{{ competition['discipline'] }}
					</div>
					<div v-if="competition['targets']" class="event-header-info-item">
						<span class="event-icon event-icon-target"></span>{{ competition['targets'] }}
					</div>
					<div v-if="competition['registration']" class="event-header-info-item">
						<span class="event-icon event-icon-members"></span>{{ competition['max_shooters'] - competition['shooters'] }} {{ (competition['max_shooters'] - competition['shooters']) | morph('место', 'места', 'мест') }} осталось
					</div>
					<div v-else-if="competition['shooters']" class="event-header-info-item">
						<span class="event-icon event-icon-members"></span>{{ competition['shooters'] }} {{ competition['shooters'] | morph('участник', 'участника', 'участников') }}
					</div>
				</div>
				<template v-if="$route.path.indexOf('registration') < 0">
					<div class="event-header-info-right-container">
						<div v-if="competition['registration']" class="event-header-info-right">
							<nuxt-link :to="competition['url']+'registration/'" class="btn btn-gold" :class="{disabled: !$store.getters.isAuthorized}">зарегистрироваться</nuxt-link>
							<div class="reg-days-left">До конца регистрации осталось {{ registrationDays }} {{ registrationDays | morph('день', 'дня', 'дней') }}</div>
						</div>
						<div v-else class="event-header-info-right">
							<a href="#" class="btn btn-white-border disabled">регистрация закрыта</a>
						</div>
						<div v-if="competition['registration'] && competition['price']" class="event-price-info">
							<div class="event-price-info-title">Стартовый взнос:</div>
							<div class="event-price">{{ !$store.getters.isAuthorized ? 'от ' : '' }}{{ competition['price']['value'] }} ₽</div>
						</div>
					</div>
				</template>
			</div>
		</HeaderContent>
		<section v-if="showTabs" class="container">
			<nav class="page-list-nav">
				<ul>
					<li>
						<nuxt-link :to="competition['url']">О соревновании</nuxt-link>
					</li>
					<li v-if="competition['tabs']['schedule']">
						<nuxt-link :to="competition['url']+'schedule/'">Расписание</nuxt-link>
					</li>
					<li v-if="competition['tabs']['groups']">
						<nuxt-link :to="competition['url']+'groups/'">Группы</nuxt-link>
					</li>
					<li v-if="competition['tabs']['participants']">
						<nuxt-link :to="competition['url']+'participants/'">Участники</nuxt-link>
					</li>
					<li v-if="competition['tabs']['results']">
						<nuxt-link :to="competition['url']+'results/'">Результаты</nuxt-link>
					</li>
					<li v-if="competition['tabs']['winners']">
						<nuxt-link :to="competition['url']+'winners/'">Победители</nuxt-link>
					</li>
					<li v-if="competition['tabs']['photo']">
						<nuxt-link :to="competition['url']+'photo/'">Фотогалерея</nuxt-link>
					</li>
				</ul>
			</nav>
		</section>
		<section class="container">
			<nuxt-child :competition="competition"></nuxt-child>
		</section>
		<section class="blue-img-section">
			<div class="container">
				<BottomResults/>
				<BottomRating/>
			</div>
		</section>

		<FutureEventsBottom/>
		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import gql from 'graphql-tag';
	import moment from 'moment';

	const competitionQuery = gql`query ($id: String!) {
		competition (id: $id) {
			id
			title
			url
			location
			date_from
			date_to
			discipline
			targets
			shooters
			max_shooters
			detail_text
			tabs
			stands
			registration
			protocols
			price {
				currency
				value
			}
		}
	}`;

	export default {
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
		},
		async asyncData (context)
		{
			try
			{
				let competitionRequest = context.app.$apollo.query({
					query: competitionQuery,
					variables: {
						id: context.route.params.id,
					},
				})

				const competition = await Promise.all([
					competitionRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([result]) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data['competition']
				})

				return {
					competition: competition,
				}
			}
			catch (e)
			{
				return context.error({
					statusCode: 404,
					message: e.message,
				})
			}
		},
		computed:
		{
			date ()
			{
				let from = moment(this.competition['date_from']);
				let to = moment(this.competition['date_to']);

				if (from.isSame(to))
					return moment(from).format('DD MMMM YYYY');
				else
					return moment(from).format('DD')+'-'+moment(to).format('DD MMMM YYYY');
			},
			registrationDays () {
				return moment(this.competition['date_from']).diff(moment(), 'days')
			},
			showTabs ()
			{
				let result = false;

				for (let tab in this.competition['tabs'])
				{
					if (result)
						break;

					if (this.competition['tabs'].hasOwnProperty(tab))
						result = this.competition['tabs'][tab];
				}

				if (this.$route.path.indexOf('registration') > -1)
					result = false

				return result;
			}
		},
		mounted()
		{

		},
	}
</script>