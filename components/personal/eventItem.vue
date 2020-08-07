<template>
	<div class="competition-row-item">
		<div v-if="item['competition']['image']" class="competition-row-item-img" :style="{backgroundImage: 'url('+item['competition']['image']+')'}"></div>
		<div class="competition-row-item-content">
			<div class="event-top">
				<div class="event-top-info">
					<div class="event-date" v-if="date">{{ date }}</div>
					<div class="event-type" v-if="item['competition']['discipline']">{{ item['competition']['discipline'] }}</div>
				</div>
				<nuxt-link :to="item['competition']['url']" class="event-title">
					{{ item['competition']['title'] }}
				</nuxt-link>
				<div class="event-location">
					<span class="icon-picker"></span> {{ item['competition']['location'] }}
				</div>
				<div v-if="item['order']" class="event-item-price">
					Стартовый взнос: <span>{{ item['order']['price']['value'] }} р</span> - Оплачен
				</div>
				<div v-if="item['price']" class="event-item-price">
					Стартовый взнос: <span>{{ item['price']['value'] }} р</span>
				</div>
			</div>
			<div class="event-btns">
				<button type="button" @click.prevent="cancel()" class="btn btn-white-border">Отменить участие</button>
				<button v-if="!item['order'] && item['price']" type="button" @click.prevent="payment()" class="btn btn-gold">Оплатить онлайн</button>
			</div>
		</div>
	</div>
</template>

<script>
	import moment from 'moment'
	import gql from 'graphql-tag'
	import SuccessModal from '~/components/successModal.vue'
	import { paymentHandler } from '~/utils/helpers'

	const cancelCompetition = gql`mutation cancelCompetitionRegistration ($id: Int!) {
		cancelCompetitionRegistration (id: $id)
	}`

	const paymentCompetition = gql`mutation competitionPayment ($id: Int!) {
		competitionPayment (competition: $id)
	}`

	export default {
		name: "personalEventItem",
		props: {
			item: {
				type: Object
			},
		},
		computed: {
			date ()
			{
				let from = moment(this.item['date_from']);
				let to = moment(this.item['date_to']);

				if (from.isSame(to))
					return moment(from).format('DD MMMM');
				else
					return moment(from).format('DD')+'-'+moment(to).format('DD MMMM');
			},
		},
		methods: {
			async cancel ()
			{
				this.$modal.confirm({
					title: '',
					content: 'Отменить участие?',
					buttons: {
						confirm: {
							text: 'Нет',
						},
						cancel: {
							text: 'Да',
							action: async () =>
							{
								try
								{
									await this.$apollo.mutate({
										mutation: cancelCompetition,
										variables: {
											id: this.item['id']
										}
									})
									.then((data) =>
									{
										if (data.errors !== undefined)
										{
											this.$modal.show(SuccessModal, {
												text: data.errors[0].message,
												error: true,
											})
										}
										else
										{
											this.$modal.show(SuccessModal, {
												text: 'Вы исключены из списка участников соревнования'
											})
											.then(() => {
												this.$nuxt.refresh()
											})
										}
									})
								}
								catch (e)
								{
									this.$modal.alert({
										title: 'Ошибка',
										content: e.message
									})
								}
							}
						}
					}
				})
			},
			async payment ()
			{
				try
				{
					await this.$apollo.mutate({
						mutation: paymentCompetition,
						variables: {
							id: this.item['competition']['id']
						}
					})
					.then((data) =>
					{
						if (data.errors !== undefined)
						{
							this.$modal.show(SuccessModal, {
								text: data.errors[0].message,
								error: true,
							})
						}
						else
						{
							paymentHandler(data.data['competitionPayment'])
						}
					})
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			}
		}
	}
</script>