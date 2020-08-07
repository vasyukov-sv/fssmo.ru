<template>
	<div class="page-content">
		<HeaderContent/>
		<div class="personal-page-container" sticky-container>
			<section class="container">
				<div class="row">
					<div class="col-md-4 order-md-1">
						<div class="personal-menu">
							<div v-sticky>
								<Menu/>
							</div>
						</div>
					</div>
					<div class="col-md-8">
						<div v-if="user['in_club'] && user['in_club']['renew']">
							<transition name="fade">
								<div class="page-alert alert-warning">
									Членство в ФССМО заканчивается {{ user['in_club']['until'] | date('DD.MM.YYYY') }}.<br>
									Чтобы продолжить членство в клуб, необходимо оплатить ежегодный членский взнос - <b>{{ user['in_club']['renew_price']['value'] }} ₽</b><br><br>
									<a href="#" @click.prevent="renewMembership" class="btn btn-blue-border">оплатить продление</a><br>
									<span class="note">(вы будете перенаправлены на сайт сбербанка для оплаты)</span>
								</div>
							</transition>
							<div class="personal-budget" v-if="user['budget'] && user['budget']['value'] && user['budget']['value'] > 0">
								<div class="personal-budget-title">
									Баланс вашего виртуального счёта: <b>{{ user['budget']['value'] }} ₽</b>
								</div>
								<div class="personal-budget-text">Вы можете использовать средства на виртуальном счёте для оплаты стартовых взносов на будущих соревнованиях</div>
							</div>
						</div>
						<form class="personal-data" method="post" @submit.prevent="update">
							<h4>Основные данные</h4>
							<div class="row">
								<div class="col-sm-4 order-sm-1">
									<client-only>
										<avatar-view :photo="user['avatar']"></avatar-view>
									</client-only>
								</div>
								<div class="col-sm-8">
									<div class="page-default-form">
										<div class="form-group form-group-label-inline">
											<label class="form-label">Фамилия</label>
											<div class="input-container">
												<input type="text" name="last_name" v-model="form.last_name" :class="{error: $v.form.last_name.$error}" @change="$v.form.last_name.$touch()">
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">Имя</label>
											<div class="input-container">
												<input type="text" name="name" v-model="form.name" :class="{error: $v.form.name.$error}" @change="$v.form.name.$touch()">
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">Телефон</label>
											<div class="input-container">
												<input-phone name="phone" v-model="form.phone" :class="{error: $v.form.phone.$error}" @change="$v.form.phone.$touch()"></input-phone>
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">Дата рождения</label>
											<div class="input-container">
												<input type="date" name="birthday" v-model="form.birthday" />
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">Разряд</label>
											<div class="input-container">
												<select name="digit" v-model="form.digit">
													<option v-for="digit in digits" :value="digit['id']">{{ digit['title'] }}</option>
												</select>
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">Клуб</label>
											<div class="input-container">
												<select name="club" v-model="form.club" :disabled="form.club > 1">
													<option v-for="club in clubs" :value="club['id']">{{ club['title'] }}</option>
												</select>
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">Город</label>
											<div class="input-container">
												<input type="text" v-model="form.city">
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">e-mail</label>
											<div class="input-container">
												<input type="email" name="email" v-model="form.email" autocomplete="username" :class="{error: $v.form.email.$error}" @change="$v.form.email.$touch()">
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<div class="checkbox">
												<input type="checkbox" id="notif_agree" v-model="form.subscribe">
												<label for="notif_agree">Присылать уведомления по электронной почте</label>
											</div>
										</div>
									</div>
									<div class="page-default-form">
										<h4>Изменить пароль</h4>
										<div class="form-group form-group-label-inline">
											<label class="form-label">старый пароль</label>
											<div class="input-container">
												<input type="password" name="old_password" v-model="form.passwordOld" autocomplete="current-password" :class="{error: $v.form.passwordOld.$error}" @change="$v.form.passwordOld.$touch()">
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">новый пароль</label>
											<div class="input-container">
												<input type="password" name="password" v-model="form.password" autocomplete="new-password" :class="{error: $v.form.password.$error}" @change="$v.form.password.$touch()">
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<label class="form-label">повторите новый пароль</label>
											<div class="input-container">
												<input type="password" name="password_confirm" v-model="form.passwordConfirm" autocomplete="new-password" :class="{error: $v.form.passwordConfirm.$error}" @change="$v.form.passwordConfirm.$touch()">
											</div>
										</div>

										<div class="form-group form-group-label-inline">
											<div class="checkbox">
												<input type="checkbox" id="person_agree" v-model="form.agreement" :class="{error: $v.form.agreement.$error}" @change="$v.form.agreement.$touch()">
												<label for="person_agree">Я согласен с условиями обработки и хранения персональных данных</label>
											</div>
										</div>
										<div class="form-group form-group-label-inline">
											<div class="form-btn">
												<button type="submit" class="btn btn-gold">сохранить</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</section>
		</div>

		<section class="blue-img-section">
			<div class="container">
				<bottomResults/>
				<bottomRating/>
			</div>
		</section>
		<FutureEventsBottom/>
		<bottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import AvatarView from '~/components/personal/avatar.vue'
	import InputPhone from '~/components/inputPhone'
	import Menu from '~/components/personal/menu.vue'
	import SuccessModal from '~/components/successModal.vue'

	import { required, email, sameAs, requiredIf } from 'vuelidate/lib/validators'
	import { userUpdateMutation, userProfileQuery } from '~/utils/query.gql'
	import moment from 'moment';
	import gql from 'graphql-tag'
	import { paymentHandler } from '~/utils/helpers'

	const renewMembership = gql`mutation ($payment: String!) {
		renewMembership (payment: $payment)
	}`;

	export default {
		middleware: 'authorization',
		components: {
			InputPhone,
			AvatarView,
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Menu,
		},
		async asyncData (context)
		{
			try
			{
				let userProfile = context.app.$apollo.query({
					query: userProfileQuery
				})

				const profile = await Promise.all([
					userProfile,
					context.store.dispatch('getPageInfo')
				])
				.then(([result]) =>
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
				form: {
					name: '',
					last_name: '',
					email: '',
					phone: '',
					club: 1,
					digit: 1,
					city: '',
					birthday: '',
					passwordOld: '',
					password: '',
					passwordConfirm: '',
					agreement: false,
					subscribe: false,
				},
			};
		},
		created ()
		{
			if (this.user  !== undefined)
			{
				this.form.name = this.user['name'];
				this.form.last_name = this.user['last_name'];
				this.form.email = this.user['email'];
				this.form.phone = this.user['phone'];
				this.form.club = this.user['club'];
				this.form.digit = this.user['digit'];
				this.form.city = this.user['city'];
				this.form.subscribe = this.user['subscribe'];
				this.form.agreement = this.user['agreement'];
				this.form.birthday = moment(this.user['birthday']).format('YYYY-MM-DD');
			}
		},
		validations: {
			form: {
				name: {
					required
				},
				last_name: {
					required
				},
				phone: {
					required
				},
				email: {
					required,
					email
				},
				passwordOld: {
					required: requiredIf('password')
				},
				password: {
					required: requiredIf('passwordOld')
				},
				passwordConfirm: {
					required: requiredIf('passwordOld'),
					sameAsPassword: sameAs('password')
				},
				agreement: {
					required (val) {
						return val;
					}
				},
			},
		},
		methods: {
			async update ()
			{
				this.$v.$touch()

				if (this.$v.$invalid)
					return

				try
				{
					await this.$apollo.mutate({
						mutation: userUpdateMutation,
						variables: {
							user: {
								name: this.form.name,
								last_name: this.form.last_name,
								email: this.form.email,
								phone: this.form.phone,
								city: this.form.city,
								club: this.form.club,
								digit: this.form.digit,
								birthday: this.form.birthday,
								password_old: this.form.passwordOld,
								password: this.form.password,
								password_confirm: this.form.passwordConfirm,
								subscribe: this.form.subscribe,
								agreement: this.form.agreement,
							}
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
								text: 'Изменения сохранены'
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
			},
			async renewMembership ()
			{
				try
				{
					const result = await this.$apollo.mutate({
						mutation: renewMembership,
						variables: {
							payment: 'online'
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['renewMembership']
					})

					if (typeof result['order'] !== 'undefined')
						paymentHandler(result['order'])
				}
				catch (e)
				{
					this.$modal.show(SuccessModal, {
						text: e.message,
						error: true,
					})
				}
			}
		}
	}
</script>
