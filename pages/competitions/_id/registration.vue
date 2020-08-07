<template>
	<div class="page-section-content">
		<div class="competition-registration">
			<h3>Регистрация на соревнование</h3>
			<form action="" class="competition-registration-form" method="post" @submit.prevent="send">
				<div class="page-default-form">
					<div class="form-section">
						<h4>Участник</h4>

						<div class="form-group form-group-label-inline">
							<label class="form-label">Фамилия</label>
							<div class="input-container">
								<input type="text" v-model="last_name" :class="{error: $v.last_name.$error}" @change="$v.last_name.$touch()">
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<label class="form-label">Имя</label>
							<div class="input-container">
								<input type="text" v-model="name" :class="{error: $v.name.$error}" @change="$v.name.$touch()">
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<label class="form-label">Отчество</label>
							<div class="input-container">
								<input type="text" v-model="middle_name" :class="{error: $v.middle_name.$error}" @change="$v.middle_name.$touch()">
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<label class="form-label">Город</label>
							<div class="input-container">
								<input type="text" v-model="city" :class="{error: $v.city.$error}" @change="$v.city.$touch()">
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<label class="form-label">Разряд</label>
							<div class="input-container">
								<select name="digit" v-model="digit">
									<option v-for="digit in digits" :value="digit['id']">{{ digit['title'] }}</option>
								</select>
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<label class="form-label">клуб</label>
							<div class="input-container">
								<select name="club" v-model="club">
									<option v-for="club in clubs" :value="club['id']">{{ club['title'] }}</option>
								</select>
							</div>
						</div>

					</div>
					<div v-if="competition['price']" class="form-section">
						<h4>Оплата стартового взноса - {{ competition['price']['value'] }} ₽</h4>
						<div class="form-group">
							<div class="radio">
								<input type="radio" v-model="payment" id="payment_online" name="payment" value="online" />
								<label for="payment_online">Оплата онлайн</label>
							</div>
						</div>
						<div v-if="user['budget'] && user['budget']['value'] > 0" class="form-group">
							<div class="radio">
								<input type="radio" v-model="payment" id="payment_balance" name="payment" value="balance" />
								<label v-if="user['budget']['value'] < competition['price']['value']" for="payment_balance">
									Оплата онлайн + с баланса <b>(ваш баланс {{ user['budget']['value'] }}  ₽)</b>
								</label>
								<label v-else for="payment_balance">
									Оплата с баланса <b>(ваш баланс {{ user['budget']['value'] }}  ₽)</b>
								</label>
							</div>
						</div>
						<div class="form-group">
							<div class="radio">
								<input type="radio" v-model="payment" id="payment_offline" name="payment" value="offline" />
								<label for="payment_offline">Оплата наличными на соревновании</label>
							</div>
						</div>
					</div>
					<div class="form-section">
						<h4>Контактная информация</h4>

						<div class="form-group form-group-label-inline">
							<label class="form-label">Телефон</label>
							<div class="input-container">
								<InputPhone v-model="phone" :class="{error: $v.phone.$error}" @change="$v.phone.$touch()"></InputPhone>
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<label class="form-label">email</label>
							<div class="input-container">
								<input type="text" placeholder="email" v-model="email" :class="{error: $v.email.$error}" @change="$v.email.$touch()">
							</div>
						</div>

						<div class="form-group form-group-label-inline">
							<div class="form-btn">
								<button type="submit" class="btn btn-gold">Регистрация</button>
							</div>
						</div>

					</div>
				</div>
			</form>

		</div>

	</div>
</template>

<script>
	import InputPhone from '~/components/inputPhone'
	import SuccessModal from '~/components/successModal.vue'
	import { paymentHandler } from '~/utils/helpers'

	import gql from 'graphql-tag'
	import { required, email } from 'vuelidate/lib/validators'
	import { userProfileQuery } from '~/utils/query.gql'

	const registrationMutation = gql`mutation ($competition: Int!, $input: CompetitionRegistrationInput!, $payment: String!) {
		competitionRegistration (competition: $competition, data: $input, payment: $payment)
	}`;

	export default {
		components: {
			InputPhone
		},
		props: {
			competition: {
				type: Object
			}
		},
		middleware: ['authorization'],
		async asyncData (context)
		{
			try
			{
				const profile = await context.app.$apollo.query({
					query: userProfileQuery
				})
				.then((result) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return result.data
				})

				return {
					user: profile['user'],
					clubs: profile['clubs'],
					digits: profile['digits'],
				}
			}
			catch (e)
			{
				return context.error({
					statusCode: 500,
					message: e.message,
				})
			}
		},
		data () {
			return {
				name: '',
				last_name: '',
				middle_name: '',
				email: '',
				phone: '',
				city: '',
				digit: 1,
				club: 1,
				payment: 'online',
			}
		},
		validations: {
			city: {
				required
			},
			name: {
				required
			},
			last_name: {
				required
			},
			middle_name: {
				required
			},
			phone: {
				required
			},
			email: {
				required,
				email
			},
		},
		created ()
		{
			if (this.competition['registration'] === false)
				this.$router.push(this.competition['url'])

			this.name = this.user.name
			this.last_name = this.user.last_name
			this.middle_name = this.user.middle_name
			this.email = this.user.email
			this.phone = this.user.phone
			this.city = this.user.city
			this.digit = this.user.digit
			this.club = this.user.club

			if (this.digit < 1)
				this.digit = 1

			if (this.club < 1)
				this.club = 1

			if (!this.competition['price'])
				this.payment = 'offline'
		},
		mounted () {
			this.$bus.$on('logout', this.onLogout)
		},
		beforeDestroy () {
			this.$bus.$off('logout', this.onLogout)
		},
		methods: {
			async send ()
			{
				this.$v.$touch()

				if (this.$v.$invalid)
					return

				try
				{
					const result = await this.$apollo.mutate({
						mutation: registrationMutation,
						variables: {
							competition: this.competition['id'],
							input: {
								name: this.name,
								last_name: this.last_name,
								middle_name: this.middle_name,
								email: this.email,
								phone: this.phone,
								city: this.city,
								digit: this.digit,
								club: this.club,
							},
							payment: this.payment,
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['competitionRegistration']
					})

					if (typeof result['order'] !== 'undefined')
					{
						paymentHandler(result['order'])
					}
					else
					{
						this.$modal.show(SuccessModal, {
							text: 'Вы успешно зарегистрированы на соревнование!'
						})
						.then(() => {
							this.$router.push(this.competition['url']+'participants/')
						})
					}
				}
				catch (e)
				{
					this.$modal.show(SuccessModal, {
						text: e.message,
						error: true,
					})
				}
			},
			onLogout () {
				this.$router.push('/')
			},
		},
	}
</script>
